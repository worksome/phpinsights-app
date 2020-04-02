<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp;

use NunoMaduro\PhpInsights\Application\Console\Contracts\Formatter;
use Symfony\Component\Console\Output\NullOutput;

class Analyser
{
    private PhpInsightContainer $container;

    public function __construct(PhpInsightContainer $container)
    {
        $this->container = $container;
    }

    public function analyse(Formatter $formatter): void
    {
        $this->container->getAnalyser()->analyse(
            $formatter,
            new NullOutput()
        );
    }
}
