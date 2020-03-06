<?php


namespace Worksome\PhpInsightsApp;

use NunoMaduro\PhpInsights\Application\Console\Contracts\Formatter;
use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use Worksome\PhpInsightsApp\Actions\Action;
use Worksome\PhpInsightsApp\Actions\CreateReviewAction;
use Worksome\PhpInsightsApp\Actions\UpdateBadgesAction;
use Worksome\PhpInsightsApp\Resolvers\PathResolver;

class GitHubReviewFormatter implements Formatter
{
    private string $baseDir;

    public array $comments;

    private GitHubContext $githubContext;

    private Configuration $configuration;

    public function __construct(Configuration $configuration, GitHubContext $gitHubContext)
    {
        $this->configuration = $configuration;
        $this->githubContext = $gitHubContext;
        $this->baseDir = $configuration->getDirectory();
    }

    /**
     * @param array<int, string> $metrics
     */
    public function format(InsightCollection $insightCollection, string $dir, array $metrics): void
    {
        collect([
            new CreateReviewAction($this->githubContext, $this),
            new UpdateBadgesAction($this->githubContext, $this->configuration),
        ])->each(fn(Action $action) => $action->handle($insightCollection));
    }

    public function getPathResolver(): PathResolver
    {
        return new PathResolver($this->baseDir);
    }
}