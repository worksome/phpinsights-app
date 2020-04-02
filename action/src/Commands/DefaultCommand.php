<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp\Commands;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Worksome\PhpInsightsApp\Analyser;
use Worksome\PhpInsightsApp\GitHub\GitHubContext;
use Worksome\PhpInsightsApp\GitHubReviewFormatter;
use Worksome\PhpInsightsApp\PhpInsightContainer;

class DefaultCommand extends Command
{
    private Analyser $analyser;
    private PhpInsightContainer $phpInsightContainer;
    private GitHubContext $context;

    public function __construct(PhpInsightContainer $phpInsightContainer, Analyser $analyser, GitHubContext $context)
    {
        parent::__construct('action:run');

        $this->analyser = $analyser;
        $this->phpInsightContainer = $phpInsightContainer;
        $this->context = $context;
    }

    protected function configure(): void
    {
        $this->addArgument('root_path', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $style->note(sprintf('PHP Memory limit is set to %s.', ini_get('memory_limit')));

        $workDir = $this->getWorkDir($input);
        $style->note("Running in [{$workDir}].");

        $configPath = $this->getConfigPath($workDir);
        $style->note("Getting config file from [{$configPath}]");

        $this->phpInsightContainer->replaceConfigurationFromPath($configPath, $workDir);

        $style->listing([
            "ref: {$this->context->getReference()}",
            "head_ref: {$this->context->getHeadReference()}",
            "base_ref: {$this->context->getBaseReference()}",
            "sha: {$this->context->getCommitSHA()}",
            "workspace: {$this->context->getWorkSpaceDirectory()}",
        ]);

        // Create our formatter,
        $formatter = new GitHubReviewFormatter(
            $this->phpInsightContainer->getConfiguration(),
            $this->context
        );

        // Run analyser with our formatter.
        $this->analyser->analyse($formatter);

        return 0;
    }

    private function getWorkDir(InputInterface $input): string
    {
        $inputPath = $input->getArgument('root_path');
        $fullPath = realpath($inputPath);

        if ($fullPath === false) {
            throw new InvalidArgumentException("The root path [{$inputPath}] could not be found.");
        }

        $workDir = sprintf(
            '%s/%s',
            $fullPath,
            $this->context->getInput('workingDir')
        );

        if (!file_exists($workDir)) {
            throw new InvalidArgumentException("The work directory [{$workDir}] does not exist.");
        }

        return $workDir;
    }

    private function getConfigPath(string $workDir): string
    {
        $configPath = $this->context->getInput('config_path')
            ?? ($workDir . DIRECTORY_SEPARATOR . 'phpinsights.php');

        if (!file_exists($configPath)) {
            throw new InvalidArgumentException("The config file [{$configPath}] does not exist.");
        }

        return $configPath;
    }
}
