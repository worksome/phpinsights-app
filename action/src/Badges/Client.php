<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp\Badges;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;

class Client
{
    private Guzzle $client;

    public function __construct(string $token)
    {
        $this->client = new Guzzle([
            'base_uri' => 'https://badges.phpinsights.app',
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$token}",
            ],
        ]);
    }

    public function updateBadges(string $login, string $repository, string $branch, array $payload): void
    {
        $this->client->post("/{$login}/{$repository}/{$branch}", [
            RequestOptions::JSON => $payload,
        ]);
    }
}
