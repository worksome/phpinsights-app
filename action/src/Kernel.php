<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp;

use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Worksome\PhpInsightsApp\Commands\DefaultCommand;
use Worksome\PhpInsightsApp\GitHub\GitHubContext;

class Kernel
{
    private Application $container;
    private GitHubContext $gitHubContext;

    public function __construct(Application $container, GitHubContext $gitHubContext)
    {
        $this->container = $container;
        $this->gitHubContext = $gitHubContext;
    }

    public function bootstrap(): void
    {
        @\NunoMaduro\PhpInsights\Domain\Kernel::bootstrap();
        $this->gitHubContext->boot();
    }

    public function handle(InputInterface $input, OutputInterface $output): int
    {
        $this->bootstrap();

        $kernelApplication = new \Composer\Console\Application('PHP Insights App');

        $kernelApplication->setCommandLoader(new ContainerCommandLoader($this->container, [
            'action:run' => DefaultCommand::class,
        ]));
        $kernelApplication->setDefaultCommand('action:run', true);

        return $kernelApplication->run($input, $output);
    }

    public function terminate(): void
    {
        $this->container->terminate();
    }
}
