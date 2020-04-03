<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp;

use Dotenv\Exception\InvalidPathException;
use Dotenv\Loader\Loader;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Store\StoreBuilder;

class EnvRepositoryFactory
{
    public static function createRepository(): EnvRepository
    {
        $repository = RepositoryBuilder::create()
            ->immutable()
            ->withReaders([
                new EnvConstAdapter(),
                new ServerConstAdapter(),
            ])->make();

        try {
            $envFile = StoreBuilder::create()->withPaths(__DIR__ . '/..')->make()->read();
            $envData = (new Loader())->load($repository, $envFile);
        } catch (InvalidPathException $exception) {
            $envData = [];
        }


        return new EnvRepository($repository, $envData);
    }
}
