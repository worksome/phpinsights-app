<?php

use NunoMaduro\PhpInsights\Application\ConfigResolver;
use NunoMaduro\PhpInsights\Application\Console\Analyser;
use NunoMaduro\PhpInsights\Application\Console\Definitions\AnalyseDefinition;
use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Container;
use NunoMaduro\PhpInsights\Domain\Kernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Worksome\PhpInsightsApp\GitHubContext;
use Worksome\PhpInsightsApp\GitHubReviewFormatter;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/squizlabs/php_codesniffer/autoload.php';

/**
 * Bootstraps the domain kernel.
 */
@Kernel::bootstrap();

$container = Container::make();

$workDir = sprintf("%s/%s", $_SERVER['argv'][1], GitHubContext::getInput('workingDir'));
$configPath = $workDir . DIRECTORY_SEPARATOR . '/phpinsights.php';
$configuration = ConfigResolver::resolve(
    file_exists($configPath) ? require_once $configPath : [],
    new ArrayInput(
        [
            'directory' => $workDir
        ],
        AnalyseDefinition::get()
    )
);

echo "Running in [{$workDir}]. \n";

$configurationDefinition = $container->extend(Configuration::class);
$configurationDefinition->setConcrete($configuration);

//dump(GitHubContext::getRuntimeUrl(), GitHubContext::getWorkFlowRunId(), str_split(GitHubContext::getRuntimeToken(), 1500));
//$token = GitHubContext::getGitHubToken();
//dump(str_split($token, round(strlen($token) / 2)));

/** @var Analyser $analyser */
$analyser = $container->get(Analyser::class);

$formatter = new GitHubReviewFormatter(
    $configuration,
    GitHubContext::fromEnv()
);

$analyser->analyse(
    $formatter,
    new NullOutput()
);