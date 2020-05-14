<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp;

use Dotenv\Repository\RepositoryInterface;

class EnvRepository
{
    private RepositoryInterface $repository;

    /** @var array<string, string> */
    private array $envData;

    /**
     * @param array<string, string> $envData
     */
    public function __construct(RepositoryInterface $repository, array $envData)
    {
        $this->repository = $repository;
        $this->envData = $envData;
    }

    public function get(string $key): ?string
    {
        return isset($this->envData[$key]) ? $this->envData[$key] : $this->repository->get($key);
    }
}
