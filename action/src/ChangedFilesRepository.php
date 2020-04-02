<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp;

use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;
use Worksome\PhpInsightsApp\GitHub\GitHubContext;

class ChangedFilesRepository
{
    private GitHubContext $context;

    private Collection $paths;

    public function __construct(GitHubContext $context)
    {
        $this->context = $context;

        if ($this->context->getEvent()->inPullRequest()) {
            $this->loadChangedFilesFromPullRequest();
        }
    }

    public function has(string $path): bool
    {
        return $this->paths->contains($path);
    }

    public function count(): int
    {
        return $this->paths->count();
    }

    private function loadChangedFilesFromPullRequest(): void
    {
        $process = Process::fromShellCommandline(sprintf(
            'git --no-pager diff --name-only origin/%s --diff-filter=d -- "*.php"',
            $this->context->getBaseReference(),
        ));
        $process->mustRun();
        $this->paths = Collection::make(
            array_filter(explode("\n", $process->getOutput()))
        );
    }
}
