<?php


namespace Worksome\PhpInsightsApp;


use Github\Client;
use NunoMaduro\PhpInsights\Application\Console\Contracts\Formatter;
use NunoMaduro\PhpInsights\Application\Console\Formatters\Console;
use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Container;
use NunoMaduro\PhpInsights\Domain\Contracts\HasDetails;
use NunoMaduro\PhpInsights\Domain\Contracts\Insight;
use NunoMaduro\PhpInsights\Domain\Details;
use NunoMaduro\PhpInsights\Domain\DetailsComparator;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use NunoMaduro\PhpInsights\Domain\Results;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitHubReviewFormatter implements Formatter
{
    private string $baseDir;

    public array $comments;

    public Client $github;

    public GitHubContext $githubContext;

    public function __construct(string $baseDir, Client $github, GitHubContext $gitHubContext)
    {
        $this->baseDir = $baseDir;
        $this->github = $github;
        $this->githubContext = $gitHubContext;
    }

    /**
     * @param \NunoMaduro\PhpInsights\Domain\Insights\InsightCollection $insightCollection
     * @param string $dir
     * @param array<int, string> $metrics
     */
    public function format(InsightCollection $insightCollection, string $dir, array $metrics): void
    {
        $result = $insightCollection->results();
        $comments = $this->toComments($insightCollection);

        $reviewStatus = $this->getReviewStatus($result, $comments);

        $this->github->pullRequest()->reviews()->create(
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
    }

    private function getDescription(string $reviewStatus): string
    {
        if ($reviewStatus === Review::APPROVE) {
            return 'PHP Insights found nothing wrong, your code is near perfect!';
        }

        if ($reviewStatus === Review::COMMENT) {
            return 'PHP Insights has some concerns, please look into it.';
        }

        return 'PHP Insights is not happy, please look into the comments, so we can be friends again.';
    }

    private function getReviewStatus(Results $result, array $comments): string
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

    private function getRelativePath(string $file): string
    {
        return str_replace($this->baseDir . DIRECTORY_SEPARATOR, '', $file);
    }

    private function formatMessage(Details $detail, Insight $insight): string
    {
        $message = "[{$insight->getTitle()}] ";

        if ($detail->hasMessage()) {
            $message .= $detail->getMessage();
        }

        return $message;
    }

    /**
     * @param InsightCollection $insightCollection
     */
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

                $file = $this->getRelativePath($detail->getFile());
                if (!array_key_exists($file, $errors)) {
                    $errors[$file] = [];
                }

                $message = $this->formatMessage($detail, $insight);
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