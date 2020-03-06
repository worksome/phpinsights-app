<?php

use Github\Client as GitHubClient;
use NunoMaduro\PhpInsights\Application\ConfigResolver;
use NunoMaduro\PhpInsights\Application\Console\Analyser;
use NunoMaduro\PhpInsights\Application\Console\Definitions\AnalyseDefinition;
use NunoMaduro\PhpInsights\Application\Console\Formatters\GithubAction;
use NunoMaduro\PhpInsights\Application\Console\Formatters\Multiple as MultiFormatter;
use NunoMaduro\PhpInsights\Application\DirectoryResolver;
use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Container;
use NunoMaduro\PhpInsights\Domain\Kernel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Worksome\PhpInsightsApp\GitHubContext;
use Worksome\PhpInsightsApp\GitHubReviewFormatter;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/squizlabs/php_codesniffer/autoload.php';

/**
 * Bootstraps the domain kernel.
 */
@Kernel::bootstrap();

$container = Container::make();
dd(getenv('INPUT_WORKINGDIR'));

$configuration = ConfigResolver::resolve(
    [],
    new ArrayInput(
        [
            GitHubContext::getInput('workingDir', $_SERVER['argv'][1])
        ],
        AnalyseDefinition::get()
    )
);

echo "Running in [{$configuration->getDirectory()}]. \n";

$configurationDefinition = $container->extend(Configuration::class);
$configurationDefinition->setConcrete($configuration);

dump(GitHubContext::getRuntimeUrl(), GitHubContext::getWorkFlowRunId(), str_split(GitHubContext::getRuntimeToken(), 1500));
$token = GitHubContext::getGitHubToken();
dump(str_split($token, round(strlen($token) / 2)));
dump([
    'ref' => getenv('GITHUB_REF'),
    'head_ref' => getenv('GITHUB_HEAD_REF'),
    'base_ref' => getenv('GITHUB_BASE_REF'),
]);

/** @var Analyser $analyser */
$analyser = $container->get(Analyser::class);

$formatter = new MultiFormatter([
    // new GithubAction(new ArrayInput([]), new ConsoleOutput()),
    new GitHubReviewFormatter(
        $configuration,
        GitHubContext::fromEnv()
    )
]);

$results = $analyser->analyse(
    $formatter,
    new NullOutput()
);