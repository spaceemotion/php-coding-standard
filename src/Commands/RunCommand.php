<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Commands;

use InvalidArgumentException;
use Spaceemotion\PhpCodingStandard\Cli;
use Spaceemotion\PhpCodingStandard\Config;
use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\ConsoleFormatter;
use Spaceemotion\PhpCodingStandard\Formatter\GithubActionFormatter;
use Spaceemotion\PhpCodingStandard\Tools\Tool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_filter;
use function array_map;
use function exec;
use function in_array;
use function ltrim;
use function substr;

class RunCommand extends Command
{
    public const SUCCESS = 0;

    /** @var string */
    protected static $defaultName = 'run';

    /** @var Tool[] */
    private $tools = [];

    /** @var Config */
    private $config;

    public function addTool(Tool $tool): void
    {
        $this->tools[] = $tool;
    }

    protected function configure(): void
    {
        $this->addOption(
            Cli::PARAMETER_SKIP,
            's',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Disables the list of tools during the run (comma-separated list)'
        );

        $this->addOption(
            Cli::PARAMETER_ONLY,
            'o',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Only executes the list of tools during the run (comma-separated list)'
        );

        $this->addOption(
            Cli::FLAG_CONTINUE,
            null,
            InputOption::VALUE_NONE,
            'Run the next check even if the previous one failed'
        );

        $this->addOption(
            Cli::FLAG_INTERACTIVE,
            null,
            InputOption::VALUE_NONE,
            'Force interactive mode'
        );

        $this->addOption(
            Cli::FLAG_FIX,
            null,
            InputOption::VALUE_NONE,
            'Try to fix any linting errors'
        );

        $this->addOption(
            Cli::FLAG_HIDE_SOURCE,
            null,
            InputOption::VALUE_NONE,
            'Hides the "source" lines from console output'
        );

        $this->addOption(
            Cli::FLAG_LINT_STAGED,
            null,
            InputOption::VALUE_NONE,
            'Uses "git diff" to determine staged files to lint'
        );

        $this->addOption(
            Cli::FLAG_CI,
            null,
            InputOption::VALUE_NONE,
            'Changes the output format to GithubActions for better CI integration'
        );

        $this->addOption(
            Cli::FLAG_NO_FAIL,
            null,
            InputOption::VALUE_NONE,
            'Only returns with exit code 0, regardless of any errors/warnings'
        );

        $this->addArgument(
            'files',
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'List of files to parse instead of the configured sources'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->config = new Config($output);

        $files = $this->getFiles($input);

        if ($files === []) {
            $output->writeln('No files specified');
            return self::SUCCESS;
        }

        $context = new Context($this->config);
        $context->isFixing = (bool) $input->getOption(Cli::FLAG_FIX) || $this->config->shouldAutoFix();
        $context->runningInCi = (bool) $input->getOption(Cli::FLAG_CI);
        $context->files = $files;

        if ($context->runningInCi) {
            $input->setInteractive(false);
        }

        if ((bool) $input->getOption(Cli::FLAG_INTERACTIVE)) {
            $input->setInteractive(true);
        }

        $success = $this->executeContext($input, $output, $context);

        $hideSource = (bool) $input->getOption(Cli::FLAG_HIDE_SOURCE);

        $formatter = $context->runningInCi
            ? new GithubActionFormatter($hideSource)
            : new ConsoleFormatter($hideSource);

        $formatter->format($context->result, new SymfonyStyle($input, $output));

        return (bool) $input->getOption(Cli::FLAG_NO_FAIL) ? self::SUCCESS : (int) ! $success;
    }

    private function executeContext(InputInterface $input, OutputInterface $output, Context $context): bool
    {
        $grayStyle = $this->getOutputStyle('#777');

        $output->getFormatter()->setStyle('gray', $grayStyle);

        $skipped = (array) $input->getOption(Cli::PARAMETER_SKIP);
        $only = (array) $input->getOption(Cli::PARAMETER_ONLY);

        $continue = (bool) $input->getOption(Cli::FLAG_CONTINUE) || $this->config->shouldContinue();
        $success = true;

        foreach ($this->tools as $tool) {
            $name = $tool->getName();

            if (! $context->config->isEnabled($name)) {
                // Don't show a message for tools we don't need/have
                continue;
            }

            if (
                // Check against --skip
                in_array($name, $skipped, true)
                // Check against --only
                || ($only !== [] && ! in_array($name, $only, true))
                // Additional checks
                || ! $tool->shouldRun($context)
            ) {
                $output->writeln("<comment>-</comment> {$name}: <comment>SKIP</comment>");
                continue;
            }

            $tool->setInput($input);
            $tool->setOutput($output);

            $start = time();
            $result = $tool->run($context);
            $timeTaken = '<gray>' . Helper::formatTime(time() - $start) . '</gray>';

            if ($result) {
                $output->writeln("✔ {$name}: <info>OK</info> ${timeTaken}");
                continue;
            }

            $output->writeln("<fg=red>✘</> {$name}: <fg=red>FAIL</> ${timeTaken}");

            $success = false;

            if (! $continue) {
                break;
            }
        }

        return $success;
    }

    /**
     * Calls 'git diff' to determine changed files.
     *
     * @return string[]
     */
    private function lintStaged(): array
    {
        $output = [];

        exec('git diff --name-status --cached', $output);

        return array_filter(array_map(static function (string $line): string {
            // Only count added or modified files
            return $line[0] === 'A' || $line[0] === 'M' ? ltrim(substr($line, 1)) : '';
        }, $output));
    }

    /**
     * @return string[]
     */
    private function getFiles(InputInterface $input): array
    {
        // 1. --lint-staged takes precedence
        if ((bool) ($input->getOption(Cli::FLAG_LINT_STAGED))) {
            return $this->lintStaged();
        }

        // 2. Then read from STDIN (e.g. ls -A1 | ....)
        $stdin = Cli::parseFilesFromInput();

        if ($stdin !== []) {
            return $stdin;
        }

        // 3. Read from argument
        $files = array_map('strval', (array) $input->getArgument('files'));

        if ($files !== []) {
            return $files;
        }

        // 4. Read from config
        return $this->config->getSources();
    }

    private function getOutputStyle(string $foreground): OutputFormatterStyle
    {
        try {
            return new OutputFormatterStyle($foreground);
        } catch (InvalidArgumentException $ex) {
            return new OutputFormatterStyle('default');
        }
    }
}
