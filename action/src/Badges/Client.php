<?php


namespace Worksome\PhpInsightsApp\Badges;


use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;

class Client
{
    private Guzzle $client;
    private string $token;

    /**
     * Client constructor.
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->client = new Guzzle([
            'base_uri' => 'https://badges.phpinsights.app',
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$token}"
            ]
        ]);
        $this->token = $token;
    }

    public function updateBadges(string $login, string $repository, string $branch, array $payload)
    {
        $this->client->post("/{$login}/{$repository}/{$branch}", [
            RequestOptions::JSON => $payload,
        ]);
    }
}