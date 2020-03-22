<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp\Actions;

use NunoMaduro\PhpInsights\Domain\Insights\InsightCollection;

interface Action
{
    public function handle(InsightCollection $insightCollection): void;
}
