<?php


namespace Worksome\PhpInsightsApp\Actions;


use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;
use Worksome\PhpInsightsApp\Badges\Client;
use Worksome\PhpInsightsApp\GitHubContext;

class UpdateBadgesAction implements Action
{
    private Client $client;

    private GitHubContext $context;

    private Configuration $configuration;

    /**
     * UpdateBadgesAction constructor.
     * @param Client $client
     * @param GitHubContext $context
     * @param Configuration $configuration
     */
    public function __construct(Client $client, GitHubContext $context, Configuration $configuration)
    {
        $this->client = $client;
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
                    'min-code' => 80,
                    'min-architecture' => 80,
                    'min-complexity' => 80,
                    'min-style' => 80,
                ],
            ]
        );
    }

    private function getBranch(): string
    {
        return $this->context::getHeadReference() ?? $this->context::getReference();
    }
}