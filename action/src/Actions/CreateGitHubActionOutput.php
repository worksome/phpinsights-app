<?php


namespace Worksome\PhpInsightsApp\Actions;


use NunoMaduro\PhpInsights\Application\Console\Formatters\GithubAction;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Worksome\PhpInsightsApp\GitHubContext;

class CreateGitHubActionOutput implements Action
{
    private GitHubContext $gitHubContext;

    private string $dir;

    /** @var array<int, string> */
    private array $metrics;

    /**
     * @param array<int, string> $metrics
     */
    public function __construct(GitHubContext $gitHubContext, string $dir, array $metrics)
    {
        $this->gitHubContext = $gitHubContext;
        $this->dir = $dir;
        $this->metrics = $metrics;
    }

    public function handle(InsightCollection $insightCollection): void
    {
        // Early exist if in a pull request.
        // Then we rely on a review.
        if ($this->gitHubContext->inPullRequest()) {
            return;
        }

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