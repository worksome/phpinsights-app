<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp\Actions;

use Github\Client;
use Illuminate\Support\Collection;
use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Contracts\HasDetails;
use NunoMaduro\PhpInsights\Domain\Contracts\Insight;
use NunoMaduro\PhpInsights\Domain\Details;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use NunoMaduro\PhpInsights\Domain\Results;
use RuntimeException;
use Worksome\PhpInsightsApp\GitHub\Comment;
use Worksome\PhpInsightsApp\GitHub\GitHubContext;
use Worksome\PhpInsightsApp\GitHubReviewFormatter;
use Worksome\PhpInsightsApp\Review;

class CreateReview implements Action
{
    private const MAX_ISSUES = 100;

    private Client $client;
    private GitHubContext $githubContext;
    private GitHubReviewFormatter $formatter;
    private Configuration $configuration;

    public function __construct(GitHubContext $context, GitHubReviewFormatter $formatter, Configuration $configuration)
    {
        $this->client = $context->getGitHubClient('comfort-fade-preview');
        $this->githubContext = $context;
        $this->formatter = $formatter;
        $this->configuration = $configuration;
    }

    public function handle(InsightCollection $insightCollection): void
    {
        // Early exist if not a pull request.
        if (!$this->githubContext->getEvent()->inPullRequest()) {
            return;
        }

        // Create a draft pull request
        $reviewId = $this->createDraftPullRequest();

        // Create comments
        $comments = $this->createComments($insightCollection, $reviewId);

        // Submit draft pull request.
        $this->submitDraftPullRequest($insightCollection, $comments, $reviewId);
    }

    public function submitDraftPullRequest(InsightCollection $insightCollection,
                                           Collection $comments,
                                           string $reviewId): void
    {
        $results = $insightCollection->results();
        $reviewStatus = $this->getReviewStatus($results, $comments->isNotEmpty());
        $issues = collect($insightCollection->all())
            ->filter(static fn (Insight $insight): bool => $insight instanceof HasDetails && $insight->hasIssue())
            ->flatMap(static fn (HasDetails $insight) => $insight->getDetails())
            ->count();

        $this->client->graphql()->fromFile(
            __DIR__ . '/../queries/submitDraftPullRequest.graphql',
            [
                'reviewId' => $reviewId,
                'body' => CreateReview::getDescription($results, $reviewStatus, $issues),
                'event' => $reviewStatus,
            ]
        );
    }

    public function createComments(InsightCollection $insightCollection, string $reviewId): Collection
    {
        $changedFiles = $this->githubContext->getChangedFiles();
        var_dump("There are {$changedFiles->count()} changed files");

        return collect($insightCollection->all())
            ->filter(static fn (Insight $insight): bool => $insight instanceof HasDetails && $insight->hasIssue())
            ->mapToGroups(static fn (HasDetails $insight) => [$insight->getTitle() => $insight->getDetails()])
            ->map(static fn (Collection $collection) => $collection->flatten(1))
            // Remove all details which doesn't have a file.
            ->map(static fn (Collection $collection) => $collection->filter(
                static fn (Details $details) => $details->hasFile()
            ))
            // Map it to comments.
            ->flatMap(fn (Collection $collection, string $title) => $collection
                ->map(fn (Details $details) => new Comment(
                    $details,
                    $title,
                    $this->formatter->getPathResolver()
                ))
            )
            ->filter(static fn (Comment $comment) => $changedFiles->has($comment->getPath()))
            ->each(static fn (Comment $comment) => var_dump("Adding pull request for {$comment->getPath()}"))
            // Take the first 100 issues, to limit how much data we send.
            ->take(self::MAX_ISSUES)
            // Chunk by 10, so we create 10 comments per request.
            ->chunk(10)
            // Map each chunk to a mutation.
            ->map(static function (Collection $chunk) use ($reviewId) {
                $innerMutations = $chunk->map(static fn (Comment $comment, int $key) => [
                    'innerMutation' => "comment{$key}: addPullRequestReviewThread(
                        input: {
                          pullRequestReviewId: \$reviewId
                          path: \$path{$key}
                          body: \$body{$key}
                          line: \$line{$key}
                          side: RIGHT
                        }
                      ) {
                        clientMutationId
                      }",
                    'variables' => [
                        "path{$key}" => ['type' => 'String!', 'value' => $comment->getPath()],
                        "body{$key}" => ['type' => 'String!', 'value' => $comment->getBody()],
                        "line{$key}" => ['type' => 'Int!', 'value' => $comment->getLine()],
                    ],
                ]);
                $variables = $innerMutations->pluck('variables')
                    ->mapWithKeys(static fn ($variables) => $variables)
                    ->put('reviewId', ['type' => 'String!', 'value' => $reviewId]);
                $innerMutation = $innerMutations->pluck('innerMutation')->join(' ');

                $mutationVariables = $variables
                    ->map(static fn ($info, $variableName) => "\${$variableName}: {$info['type']}")
                    ->join(' ');

                return [
                    'mutation' => "mutation({$mutationVariables}) { {$innerMutation} }",
                    'variables' => $variables->map->value->all(),
                ];
            })
            // Run the mutations
            ->each(fn (array $mutation) => $this->client->graphql()->execute(
                $mutation['mutation'],
                $mutation['variables']
            ));
    }

    public function createDraftPullRequest(): string
    {
        [
            'data' => ['addPullRequestReview' => ['pullRequestReview' => ['id' => $reviewId] ] ],
            'errors' => $errors,
        ] = $this->client->graphql()->fromFile(
            __DIR__ . '/../queries/createDraftPullRequest.graphql',
            [
                'prId' => $this->githubContext->getEvent()->getPullRequestNodeId(),
            ]
        ) + ['errors' => null];

        if ($reviewId === null) {
            echo printf(
                "Failed creating pull request review, trying to get current draft pull request. [%s]\n",
                json_encode($errors)
            );
            return $this->getCurrentDraftPullRequest();
        }
        return $reviewId;
    }

    private function getReviewStatus(Results $result, bool $hasComments): string
    {
        $checks = [
            $result->getCodeQuality() < $this->configuration->getMinQuality(),
            $result->getComplexity() < $this->configuration->getMinComplexity(),
            $result->getStructure() < $this->configuration->getMinArchitecture(),
            $result->getStyle() < $this->configuration->getMinStyle(),
            !$this->configuration->isSecurityCheckDisabled() && $result->getTotalSecurityIssues() > 0,
        ];

        if (collect($checks)->contains(true)) {
            return Review::REQUEST_CHANGES;
        }

        return $hasComments === true ? Review::COMMENT : Review::APPROVE;
    }

    private function getCurrentDraftPullRequest(): string
    {
        [
            'data' => ['repository' => ['pullRequest' => ['reviews' => ['nodes' => [ [ 'id' => $draftPrId ] ] ] ] ] ],
            'errors' => $errors,
        ] = $this->client->graphql()->fromFile(
            __DIR__ . '/../queries/getCurrentDraftPullRequest.graphql',
            [
                'owner' => $this->githubContext->getEvent()->getRepositoryOwnerLogin(),
                'repository' => $this->githubContext->getEvent()->getRepositoryName(),
                'pullRequestNumber' => $this->githubContext->getEvent()->getPullRequestNumber(),
            ]
        );

        if ($draftPrId === null) {
            throw new RuntimeException(sprintf(
                'No current draft pull request open. [%s]',
                json_encode($errors)
            ));
        }

        return $draftPrId;
    }

    private static function getDescription(Results $results, string $reviewStatus, int $issues): string
    {
        $table = sprintf(
            "| Code | Complexity | Architecture | Style |\n|:-:|:-:|:-:|:-:|\n|%s%%|%s%%|%s%%|%s%%|",
            $results->getCodeQuality(),
            $results->getComplexity(),
            $results->getStructure(),
            $results->getStyle(),
        );

        $prepend = "Found {$issues}  issues in the code.\n";
        if ($issues > self::MAX_ISSUES) {
            $prepend .= sprintf("Too many issues, limiting to only show the first %d.\n\n", self::MAX_ISSUES);
        }

        if ($reviewStatus === Review::APPROVE) {
            return "{$prepend}PHP Insights found nothing wrong, your code is near perfect!\n{$table}";
        }

        if ($reviewStatus === Review::COMMENT) {
            return "{$prepend}PHP Insights has some concerns, please look into it.\n{$table}";
        }

        return "{$prepend}PHP Insights is not happy, please look into the comments, so we can be friends again.\n{$table}";
    }
}
