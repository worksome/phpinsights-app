<?php


namespace Worksome\PhpInsightsApp;


use Github\Client;
use stdClass;

class GitHubContext
{
    public const GITHUB_EVENT_PATH = 'GITHUB_EVENT_PATH';
    public const GITHUB_SHA = 'GITHUB_SHA';

    public stdClass $context;

    private static ?Client $client = null;

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

    public static function getRuntimeUrl(): string
    {
        return getenv('ACTIONS_RUNTIME_URL');
    }

    public static function getRuntimeToken(): string
    {
        return getenv('ACTIONS_RUNTIME_TOKEN');
    }

    public static function getWorkFlowRunId(): string
    {
        return getenv('GITHUB_RUN_ID');
    }

    public static function getWorkSpaceDirectory(): string
    {
        return getenv('GITHUB_WORKSPACE');
    }

    public static function getReference(): string
    {
        return getenv('GITHUB_REF');
    }

    public static function getHeadReference(): ?string
    {
        return getenv('GITHUB_HEAD_REF');
    }

    public static function getBaseReference(): ?string
    {
        return getenv('GITHUB_BASE_REF');
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

    public static function getGitHubToken(string $name = 'repo token'): string
    {
        return self::getInput($name);
    }

    public static function getGitHubClient(string $name = 'repo token'): Client
    {
        if (self::$client !== null) {
            return self::$client;
        }

        self::$client = new Client();
        self::$client->authenticate(self::getGitHubToken($name), null, Client::AUTH_HTTP_TOKEN);
        return self::$client;
    }
}