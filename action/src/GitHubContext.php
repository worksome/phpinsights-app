<?php


namespace Worksome\PhpInsightsApp;


use stdClass;

class GitHubContext
{
    public const GITHUB_EVENT_PATH = 'GITHUB_EVENT_PATH';
    public const GITHUB_SHA = 'GITHUB_SHA';

    public stdClass $context;

    /**
     * Context constructor.
     * @param stdClass $context
     */
    public function __construct(stdClass $context)
    {
        $this->context = $context;
    }

    public static function fromPath(string $path): self
    {
        return new GitHubContext(json_decode(file_get_contents($path)));
    }

    public static function fromEnv(string $env = self::GITHUB_EVENT_PATH): self
    {
        return self::fromPath(getenv($env));
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

    public function getPullRequestNumber(): int
    {
        return $this->context->pull_request->number;
    }

    public static function getInput(string $name, $default = null)
    {
        $value = getenv('INPUT_'. mb_strtoupper(str_replace(' ', '_', $name)));

        if ($value === false) {
            return $default;
        }

        return trim($value);
    }

    public static function getCommitSHA(): string
    {
        return getenv(self::GITHUB_SHA);
    }
}