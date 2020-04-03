<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp\Actions;

use NunoMaduro\PhpInsights\Application\Console\Formatters\GithubAction;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class CreateGitHubActionOutput implements Action
{
    /** @var array<int, string> */
    private array $metrics;

    /**
     * @param array<int, string> $metrics
     */
    public function __construct(array $metrics)
    {
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
            $this->metrics
        );
    }
}
