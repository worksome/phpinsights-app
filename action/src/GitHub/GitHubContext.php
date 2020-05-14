<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp\GitHub;

use Github\Client;
use Symfony\Component\Process\Process;
use Worksome\PhpInsightsApp\ChangedFilesRepository;
use Worksome\PhpInsightsApp\EnvRepository;

class GitHubContext
{
    public const GITHUB_EVENT_PATH = 'GITHUB_EVENT_PATH';
    public const GITHUB_SHA = 'GITHUB_SHA';

    private EnvRepository $repository;
    private GitHubEvent $event;
    private ChangedFilesRepository $changedFiles;

    public function __construct(EnvRepository $envRepository)
    {
        $this->repository = $envRepository;
    }

    public function boot(): void
    {
        Process::fromShellCommandline(sprintf(
            'git fetch --no-tags --prune --depth=1 origin %s',
            self::getBaseReference()
        ))->mustRun();

        $this->event = GitHubEvent::fromPath(
            $this->getGitHubEventPath()
        );
        $this->changedFiles = new ChangedFilesRepository($this);
    }

    public function getGitHubEventPath(): string
    {
        return $this->repository->get(self::GITHUB_EVENT_PATH);
    }

    public function getEvent(): GitHubEvent
    {
        return $this->event;
    }

    public function getChangedFiles(): ChangedFilesRepository
    {
        return $this->changedFiles;
    }

    public function getRuntimeUrl(): string
    {
        return $this->repository->get('ACTIONS_RUNTIME_URL');
    }

    public function getRuntimeToken(): string
    {
        return $this->repository->get('ACTIONS_RUNTIME_TOKEN');
    }

    public function getWorkFlowRunId(): string
    {
        return $this->repository->get('GITHUB_RUN_ID');
    }

    public function getWorkSpaceDirectory(): string
    {
        return $this->repository->get('GITHUB_WORKSPACE');
    }

    public function getReference(): string
    {
        return $this->repository->get('GITHUB_REF');
    }

    public function getHeadReference(): ?string
    {
        return $this->repository->get('GITHUB_HEAD_REF');
    }

    public function getBaseReference(): string
    {
        return $this->repository->get('GITHUB_BASE_REF') ?? 'master';
    }

    public function getInput(string $name): ?string
    {
        return $this->repository->get(
            'INPUT_'. mb_strtoupper(str_replace(' ', '_', $name))
        );
    }

    public function getCommitSHA(): string
    {
        return $this->repository->get(self::GITHUB_SHA);
    }

    public function getGitHubToken(string $name = 'repo token'): string
    {
        return $this->getInput($name);
    }

    public function getGitHubClient(?string $apiVersion = null, string $name = 'repo token'): Client
    {
        $client = new Client(null, $apiVersion);
        $client->authenticate(self::getGitHubToken($name), null, Client::AUTH_HTTP_TOKEN);
        return $client;
    }
}
