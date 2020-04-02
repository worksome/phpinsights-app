<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp\GitHub;

use stdClass;

class GitHubEvent
{
    private stdClass $context;

    public function __construct(stdClass $context)
    {
        $this->context = $context;
    }

    public static function fromPath(string $path): self
    {
        return new self(json_decode(file_get_contents($path)));
    }

    public function getPullRequestNodeId(): string
    {
        return $this->context->pull_request->node_id;
    }

    public function getRepositoryOwnerLogin(): string
    {
        return $this->context->repository->owner->login;
    }

    public function getRepositoryName(): string
    {
        return $this->context->repository->name;
    }

    public function inPullRequest(): bool
    {
        return isset($this->context->pull_request);
    }

    public function getPullRequestNumber(): int
    {
        return $this->context->pull_request->number;
    }
}
