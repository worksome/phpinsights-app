<?php


namespace Worksome\PhpInsightsApp\Actions;


use Github\Client;
use NunoMaduro\PhpInsights\Domain\Contracts\HasDetails;
use NunoMaduro\PhpInsights\Domain\Contracts\Insight;
use NunoMaduro\PhpInsights\Domain\Details;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use NunoMaduro\PhpInsights\Domain\Results;
use Worksome\PhpInsightsApp\GitHubContext;
use Worksome\PhpInsightsApp\GitHubReviewFormatter;
use Worksome\PhpInsightsApp\Review;

class CreateReviewAction implements Action
{
    private Client $client;
    private GitHubContext $githubContext;
    private $formatter;

    /**
     * CreateReviewAction constructor.
     * @param GitHubContext $context
     * @param GitHubReviewFormatter $formatter
     */
    public function __construct(GitHubContext $context, GitHubReviewFormatter $formatter)
    {
        $this->client = $context::getGitHubClient();
        $this->githubContext = $context;
        $this->formatter = $formatter;
    }

    public function handle(InsightCollection $insightCollection): void
    {
        // Early exist if not a pull request.
        if (!$this->githubContext->inPullRequest()) {
            return;
        }

        $comments = $this->toComments($insightCollection);

        $reviewStatus = self::getReviewStatus($insightCollection->results(), $comments);

        $this->githubContext::getGitHubClient()->pullRequest()->reviews()->create(
            $this->githubContext->getRepositoryOwnerLogin(),
            $this->githubContext->getRepositoryName(),
            $this->githubContext->getPullRequestNumber(),
            [
                //'commit_id' => $githubContext::getCommitSHA(),
                'event' => $reviewStatus,
                'body' => $this->getDescription($reviewStatus),
                //'comments' => array_slice($comments, 0, 22, true)
            ]
        );

        // Create a draft pull request

        // Create comments

        // Submit draft pull request.
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

    private static function getReviewStatus(Results $result, array $comments): string
    {
        if ($result->getCodeQuality() < 80) {
            return Review::REQUEST_CHANGES;
        }

        if ($result->getComplexity() < 80) {
            return Review::REQUEST_CHANGES;
        }

        if ($result->getStructure() < 80) {
            return Review::REQUEST_CHANGES;
        }

        if ($result->getStyle() < 80) {
            return Review::REQUEST_CHANGES;
        }

        if ($result->getTotalSecurityIssues() > 0) {
            return Review::REQUEST_CHANGES;
        }

        return $comments === [] ? Review::APPROVE : Review::COMMENT;
    }

    private static function formatMessage(Details $detail, Insight $insight): string
    {
        $message = "[{$insight->getTitle()}] ";

        if ($detail->hasMessage()) {
            $message .= $detail->getMessage();
        }

        return $message;
    }

    public function toComments(InsightCollection $insightCollection): array
    {
        $errors = [];

        foreach ($insightCollection->all() as $insight) {
            if (!$insight instanceof HasDetails || !$insight->hasIssue()) {
                continue;
            }

            $details = $insight->getDetails();

            /** @var Details $detail */
            foreach ($details as $detail) {
                if (!$detail->hasFile()) {
                    continue;
                }

                $file = $this->formatter->getRelativePath($detail->getFile());
                if (!array_key_exists($file, $errors)) {
                    $errors[$file] = [];
                }

                $message = self::formatMessage($detail, $insight);
                // replace line 0 to line 1
                // github action write it at line 1 otherwise
                $line = $detail->hasLine() ? $detail->getLine() : 1;

                if (!array_key_exists($line, $errors[$file])) {
                    $errors[$file][$line] = $message;
                    continue;
                }

                $errors[$file][$line] .= "\n" . $message;
            }
        }

        $comments = [];
        foreach ($errors as $file => $lines) {
            foreach ($lines as $line => $message) {
                $comments[] = [
                    'path' => $file,
                    'position' => $line,
                    'body' => $message
                ];
            }
        }

        return $comments;
    }
}