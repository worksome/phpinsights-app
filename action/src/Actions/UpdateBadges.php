<?php


namespace Worksome\PhpInsightsApp\Actions;


use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use Worksome\PhpInsightsApp\Badges\Client;
use Worksome\PhpInsightsApp\Badges\Client as BadgesClient;
use Worksome\PhpInsightsApp\GitHubContext;

class UpdateBadges implements Action
{
    private Client $client;

    private GitHubContext $context;

    private Configuration $configuration;

    /**
     * UpdateBadgesAction constructor.
     */
    public function __construct(GitHubContext $context, Configuration $configuration)
    {
        $this->client = new BadgesClient($context::getGitHubToken());
        $this->context = $context;
        $this->configuration = $configuration;
    }

    public function handle(InsightCollection $insightCollection): void
    {
        $results = $insightCollection->results();

        $this->client->updateBadges(
            $this->context->getRepositoryOwnerLogin(),
            $this->context->getRepositoryName(),
            $this->getBranch(),
            [
                "summary" => [
                    'code' => $results->getCodeQuality(),
                    'architecture' => $results->getStructure(),
                    'complexity' => $results->getComplexity(),
                    'style' => $results->getStyle(),
                ],
                'requirements' => [
                    'min-code' => $this->configuration->getMinQuality(),
                    'min-architecture' => $this->configuration->getMinArchitecture(),
                    'min-complexity' => $this->configuration->getMinComplexity(),
                    'min-style' => $this->configuration->getMinStyle(),
                ],
            ]
        );
    }

    private function getBranch(): string
    {
        return $this->context::getHeadReference() ?? str_replace('refs/heads/', '', $this->context::getReference());
    }
}