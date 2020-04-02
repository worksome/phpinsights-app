<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp\Actions;

use NunoMaduro\PhpInsights\Application\Console\Formatters\GithubAction;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class CreateGitHubActionOutput implements Action
{
    private string $dir;

    /** @var array<int, string> */
    private array $metrics;

    /**
     * @param array<int, string> $metrics
     */
    public function __construct(string $dir, array $metrics)
    {
        $this->dir = $dir;
        $this->metrics = $metrics;
    }

    public function handle(InsightCollection $insightCollection): void
    {
        $actionFormatter = new GithubAction(
            new ArrayInput([]),
            new ConsoleOutput()
        );

        $actionFormatter->format(
            $insightCollection,
            $this->dir,
            $this->metrics
        );
    }
}
