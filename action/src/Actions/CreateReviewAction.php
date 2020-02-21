<?php


namespace Worksome\PhpInsightsApp\Actions;


use Github\Client;
use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;

class CreateReviewAction implements Action
{
    private Client $client;

    /**
     * CreateReviewAction constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function handle(InsightCollection $insightCollection): void
    {
        // Create a draft pull request

        // Create comments

        // Submit draft pull request.
    }
}