<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp;

use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;

class EnvRepositoryFactory
{
    public static function createRepository(): RepositoryInterface
    {
        return RepositoryBuilder::create()
            ->immutable()
            ->withReaders([
                new EnvConstAdapter(),
                new ServerConstAdapter(),
            ])->make();
    }
}
