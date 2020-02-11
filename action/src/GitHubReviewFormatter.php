<?php


namespace Worksome\PhpInsightsApp;


use NunoMaduro\PhpInsights\Application\Console\Contracts\Formatter;
use NunoMaduro\PhpInsights\Application\Console\Formatters\Console;
use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Container;
use NunoMaduro\PhpInsights\Domain\Contracts\HasDetails;
use NunoMaduro\PhpInsights\Domain\Contracts\Insight;
use NunoMaduro\PhpInsights\Domain\Details;
use NunoMaduro\PhpInsights\Domain\DetailsComparator;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitHubReviewFormatter implements Formatter
{
    private string $baseDir;

    public array $comments;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
    }

    /**
     * @param \NunoMaduro\PhpInsights\Domain\Insights\InsightCollection $insightCollection
     * @param string $dir
     * @param array<int, string> $metrics
     */
    public function format(InsightCollection $insightCollection, string $dir, array $metrics): void
    {
        $errors = [];

        foreach ($insightCollection->all() as $insight) {
            if (! $insight instanceof HasDetails || ! $insight->hasIssue()) {
                continue;
            }

            $details = $insight->getDetails();

            /** @var Details $detail */
            foreach ($details as $detail) {
                if (! $detail->hasFile()) {
                    continue;
                }

                $file = $this->getRelativePath($detail->getFile());
                if (! array_key_exists($file, $errors)) {
                    $errors[$file] = [];
                }

                $message = $this->formatMessage($detail, $insight);
                // replace line 0 to line 1
                // github action write it at line 1 otherwise
                $line = $detail->hasLine() ? $detail->getLine() : 1;

                if (! array_key_exists($line, $errors[$file])) {
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

        $this->comments = $comments;
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

}