<?php

use NunoMaduro\PhpInsights\Application\ConfigResolver;
use NunoMaduro\PhpInsights\Application\Console\Analyser;
use NunoMaduro\PhpInsights\Application\Console\Definitions\AnalyseDefinition;
use NunoMaduro\PhpInsights\Application\DirectoryResolver;
use NunoMaduro\PhpInsights\Domain\Configuration;
use NunoMaduro\PhpInsights\Domain\Container;
use NunoMaduro\PhpInsights\Domain\Kernel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/squizlabs/php_codesniffer/autoload.php';

/**
 * Bootstraps the domain kernel.
 */
@Kernel::bootstrap();

$container = Container::make();

$configuration = ConfigResolver::resolve(
    [],
    DirectoryResolver::resolve(
        new ArgvInput(
            null,
            AnalyseDefinition::get()
        )
    )
);

$configurationDefinition = $container->extend(Configuration::class);
$configurationDefinition->setConcrete($configuration);

/** @var Analyser $analyser */
$analyser = $container->get(Analyser::class);

$formatter = new \Worksome\PhpInsightsApp\Multiple([
//    new \NunoMaduro\PhpInsights\Application\Console\Formatters\GithubAction(
//        new ArrayInput([]),
//        new ConsoleOutput()
//    ),
    new \NunoMaduro\PhpInsights\Application\Console\Formatters\Json(
        new ArrayInput([]),
        new StreamOutput(
            $jsonStream = fopen('php://temp', 'r+')
        )
    ),
    $review = new \Worksome\PhpInsightsApp\GitHubReviewFormatter(
        $configuration->getDirectory()
    )
]);

$results = $analyser->analyse(
    $formatter,
    new \Symfony\Component\Console\Output\NullOutput()
);

// Rewind the stream pointer.
rewind($jsonStream);

$json = json_decode(stream_get_contents($jsonStream));

$githubContext = \Worksome\PhpInsightsApp\GitHubContext::fromEnv();
$token = \Worksome\PhpInsightsApp\GitHubContext::getInput('repo token');

$github = new \Github\Client();
$github->authenticate($token, null, $github::AUTH_HTTP_TOKEN);

$result = $github->pullRequest()->reviews()->create(
    $githubContext->getRepositoryOwnerLogin(),
    $githubContext->getRepositoryName(),
    $githubContext->getPullRequestNumber(),
    [
        //'commit_id' => $githubContext::getCommitSHA(),
        'event' => 'COMMENT',
        'body' => 'PHP Insights has some concerns, please look into it.',
        'comments' => array_slice($review->comments, 0, 22, true)
    ]
);