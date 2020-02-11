<?php


namespace Worksome\PhpInsightsApp;


use GuzzleHttp\Client;

class GitHubApi
{
    protected Client $client;

    /**
     * GitHub constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function query(string $query)
    {
        return $this->client->post('graphql', [
            'json' => [
                'query' => $query,
            ],
        ]);
    }

}