<?php


namespace Worksome\PhpInsightsApp\Actions;


use Github\Client;
use Illuminate\Support\Collection;
use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Contracts\HasDetails;
use NunoMaduro\PhpInsights\Domain\Contracts\Insight;
use NunoMaduro\PhpInsights\Domain\Details;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use NunoMaduro\PhpInsights\Domain\Results;
use Worksome\PhpInsightsApp\GitHub\Comment;
use Worksome\PhpInsightsApp\GitHubContext;
use Worksome\PhpInsightsApp\GitHubReviewFormatter;
use Worksome\PhpInsightsApp\Review;
use function Clue\StreamFilter\fun;

class CreateReviewAction implements Action
{
    private Client $client;
    private GitHubContext $githubContext;
    private GitHubReviewFormatter $formatter;
    private Configuration $configuration;

    public function __construct(GitHubContext $context, GitHubReviewFormatter $formatter, Configuration $configuration)
    {
        $this->client = $context::getGitHubClient('comfort-fade-preview');
        $this->githubContext = $context;
        $this->formatter = $formatter;
        $this->configuration = $configuration;
    }

    public function handle(InsightCollection $insightCollection): void
    {
        // Early exist if not a pull request.
        if (!$this->githubContext->inPullRequest()) {
            return;
        }

        // Create a draft pull request
        $reviewId = $this->createDraftPullRequest();

        if ($reviewId === null) {
            throw new \Exception("Failed creating Pull Request Review.");
        }

        // Create comments
        $comments = $this->createComments($insightCollection, $reviewId);

        // Submit draft pull request.
        $this->submitDraftPullRequest($insightCollection, $comments, $reviewId);
    }

    private static function getDescription(string $reviewStatus): string
    {
        if ($reviewStatus === Review::APPROVE) {
            return 'PHP Insights found nothing wrong, your code is near perfect!';
        }

        if ($reviewStatus === Review::COMMENT) {
            return 'PHP Insights has some concerns, please look into it.';
        }

        return 'PHP Insights is not happy, please look into the comments, so we can be friends again.';
    }

    private function getReviewStatus(Results $result, bool $hasComments): string
    {
        if ($result->getCodeQuality() < $this->configuration->getMinQuality()) {
            return Review::REQUEST_CHANGES;
        }

        if ($result->getComplexity() < $this->configuration->getMinComplexity()) {
            return Review::REQUEST_CHANGES;
        }

        if ($result->getStructure() < $this->configuration->getMinArchitecture()) {
            return Review::REQUEST_CHANGES;
        }

        if ($result->getStyle() < $this->configuration->getMinStyle()) {
            return Review::REQUEST_CHANGES;
        }

        if (!$this->configuration->isSecurityCheckDisabled() && $result->getTotalSecurityIssues() > 0) {
            return Review::REQUEST_CHANGES;
        }

        return $hasComments === true ? Review::COMMENT : Review::APPROVE;
    }

    /**
     * @param InsightCollection $insightCollection
     * @param Collection $comments
     * @param $reviewId
     */
    public function submitDraftPullRequest(InsightCollection $insightCollection, Collection $comments, $reviewId): void
    {
        $reviewStatus = $this->getReviewStatus($insightCollection->results(), $comments->isNotEmpty());
        $this->client->graphql()->execute(
        /** @lang GraphQL */ '
            mutation($reviewId: String! $body: String! $event: PullRequestReviewEvent!) {
                submitPullRequestReview(
                    input: {
                        pullRequestReviewId: $reviewId
                        body: $body
                        event: $event
                    }
                ) {
                    pullRequestReview {
                        id
                    }
                }
            }',
            [
                'reviewId' => $reviewId,
                'body' => $this::getDescription($reviewStatus),
                'event' => $reviewStatus
            ]
        );
    }

    /**
     * @param InsightCollection $insightCollection
     * @param $reviewId
     * @return Collection
     */
    public function createComments(InsightCollection $insightCollection, $reviewId): Collection
    {
        return collect($insightCollection->all())
            ->filter(fn(Insight $insight): bool => $insight instanceof HasDetails && $insight->hasIssue())
            ->mapToGroups(fn(HasDetails $insight) => [$insight->getTitle() => $insight->getDetails()])
            ->map(fn(Collection $collection) => $collection->flatten(1))
            // Remove all details which doesn't have a file.
            ->map(fn(Collection $collection) => $collection->filter(fn(Details $details) => $details->hasFile()))
            // Map it to comments.
            ->flatMap(fn(Collection $collection, string $title) => $collection->map(fn(Details $details) => new Comment(
                $details,
                $title,
                $this->formatter->getPathResolver()
            ))
            )
            // Chunk by 10, so we create 10 comments per request.
            ->chunk(10)
            // Map each chunk to a mutation.
            ->map(function (Collection $chunk) use ($reviewId) {
                $innerMutations = $chunk->map(function (Comment $comment, int $key) use ($reviewId) {
                    return [
                        'innerMutation' => "comment{$key}: addPullRequestReviewThread(
                            input: {
                              pullRequestReviewId: \$reviewId
                              path: \$path{$key}
                              body: \$body{$key}
                              line: \$line{$key}
                            }
                          ) {
                            clientMutationId
                          }",
                        'variables' => [
                            "path{$key}" => ['type' => 'String!', 'value' => $comment->getPath()],
                            "body{$key}" => ['type' => 'String!', 'value' => $comment->getBody()],
                            "line{$key}" => ['type' => 'Int!', 'value' => $comment->getLine()],
                        ]
                    ];
                });
                $variables = $innerMutations->pluck('variables')
                    ->mapWithKeys(fn($variables) => $variables)
                    ->put('reviewId', ['type' => 'String!', 'value' => $reviewId]);
                $innerMutation = $innerMutations->pluck('innerMutation')->join(' ');

                $mutationVariables = $variables
                    ->map(fn($info, $variableName) => "\${$variableName}: {$info['type']}")
                    ->join(' ');

                return [
                    'mutation' => "mutation({$mutationVariables}) { {$innerMutation} }",
                    'variables' => $variables->map->value->all()
                ];
            })
            // Run the mutations
            ->each(fn(array $mutation) => $this->client->graphql()->execute(
                $mutation['mutation'],
                $mutation['variables']
            ));
    }

    /**
     * @return mixed
     */
    public function createDraftPullRequest()
    {
        ['data' => ['addPullRequestReview' => ['pullRequestReview' => ['id' => $reviewId] ] ] ] = $this->client->graphql()->execute(
        /** @lang GraphQL */ '
            mutation($prId: String!) {
              addPullRequestReview(
                input: {
                  pullRequestId: $prId
                }
              ) {
                pullRequestReview {
                  id
                }
              }
            }',
            [
                'prId' => $this->githubContext->getPullRequestNodeId(),
            ]
        );

        return $reviewId;
    }
}