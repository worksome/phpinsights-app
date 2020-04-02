<?php

declare(strict_types=1);

use Dotenv\Repository\RepositoryInterface as EnvRepositoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Worksome\PhpInsightsApp\Analyser;
use Worksome\PhpInsightsApp\Application;
use Worksome\PhpInsightsApp\ChangedFilesRepository;
use Worksome\PhpInsightsApp\Commands\DefaultCommand;
use Worksome\PhpInsightsApp\EnvRepositoryFactory;
use Worksome\PhpInsightsApp\GitHub\GitHubContext;
use Worksome\PhpInsightsApp\Kernel;
use Worksome\PhpInsightsApp\PhpInsightContainer;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()->autowire()->public();

    $services->set(DefaultCommand::class);
    $services->set(Analyser::class);
    $services->set(PhpInsightContainer::class);
    $services->set(Kernel::class);
    $services->set(Application::class)->synthetic();
    $services->set(EnvRepositoryInterface::class)
        ->factory([EnvRepositoryFactory::class, 'createRepository']);
    $services->set(GitHubContext::class);
    $services->set(ChangedFilesRepository::class);
};
