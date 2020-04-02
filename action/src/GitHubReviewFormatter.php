<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp;

use Exception;
use NunoMaduro\PhpInsights\Application\Console\Contracts\Formatter;
use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use Worksome\PhpInsightsApp\Actions\Action;
use Worksome\PhpInsightsApp\Actions\CreateGitHubActionOutput;
use Worksome\PhpInsightsApp\Actions\CreateReview;
use Worksome\PhpInsightsApp\Actions\UpdateBadges;
use Worksome\PhpInsightsApp\GitHub\GitHubContext;
use Worksome\PhpInsightsApp\Resolvers\PathResolver;

class GitHubReviewFormatter implements Formatter
{
    private string $baseDir;

    private GitHubContext $githubContext;

    private Configuration $configuration;

    public function __construct(Configuration $configuration, GitHubContext $gitHubContext)
    {
        $this->configuration = $configuration;
        $this->githubContext = $gitHubContext;
        $this->baseDir = $gitHubContext->getWorkSpaceDirectory();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     *
     * @param array<int, string> $metrics
     */
    public function format(InsightCollection $insightCollection, string $dir, array $metrics): void
    {
        // As PHP Insights changes the error handle, we have to restore them, so we can see the errors again.
        set_error_handler(null);
        set_exception_handler(null);

        collect([
            new CreateReview($this->githubContext, $this, $this->configuration),
            new UpdateBadges($this->githubContext, $this->configuration),
            new CreateGitHubActionOutput($dir, $metrics),
        ])->each(static function (Action $action) use ($insightCollection): void {
            try {
                $action->handle($insightCollection);
            } catch (Exception $exception) {
                echo sprintf(
                    "Failed on action [%s] with message [%s]\n",
                    class_basename($action),
                    $exception->getMessage()
                );
            }
        });
    }

    public function getPathResolver(): PathResolver
    {
        return new PathResolver($this->baseDir);
    }
}
