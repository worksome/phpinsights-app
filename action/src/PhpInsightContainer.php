<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp;

use NunoMaduro\PhpInsights\Application\ConfigResolver;
use NunoMaduro\PhpInsights\Application\Console\Analyser as PhpInsightAnalyser;
use NunoMaduro\PhpInsights\Application\Console\Definitions\AnalyseDefinition;
use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Container as BasePhpinsightContainer;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;

class PhpInsightContainer
{
    private ContainerInterface $phpInsightContainer;

    public function __construct()
    {
        $this->phpInsightContainer = BasePhpinsightContainer::make();
    }

    public function getAnalyser(): PhpInsightAnalyser
    {
        return $this->phpInsightContainer->get(PhpInsightAnalyser::class);
    }

    public function replaceConfiguration(Configuration $configuration): void
    {
        $configurationDefinition = $this->phpInsightContainer->extend(Configuration::class);
        $configurationDefinition->setConcrete($configuration);
    }

    public function replaceConfigurationFromPath(string $configPath, string $workDir): void
    {
        $configuration = ConfigResolver::resolve(
            require $configPath,
            new ArrayInput(
                [
                    'directory' => $workDir,
                ],
                AnalyseDefinition::get()
            )
        );

        $this->replaceConfiguration($configuration);
    }

    public function getConfiguration(): Configuration
    {
        return $this->phpInsightContainer->get(Configuration::class);
    }
}
