<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use RuntimeException;
use Spaceemotion\PhpCodingStandard\Cli;
use Spaceemotion\PhpCodingStandard\Context;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function array_merge;
use function implode;
use function is_string;
use function preg_match;
use function rename;
use function sys_get_temp_dir;
use function tempnam;
use function usleep;

abstract class Tool
{
    public const CHARS = ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇'];

    /** @var string A short name for the tool to be used in the config */
    protected $name = '';

    /** @var InputInterface|null */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Runs this tool with the given context.
     */
    abstract public function run(Context $context): bool;

    /**
     * Indicates if this should should run for the given context.
     */
    public function shouldRun(Context $context): bool
    {
        return true;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Runs the given command list in sequence.
     * On windows, the .bat file will be executed instead.
     *
     * @param string $binary The raw binary name
     * @param string[] $arguments
     * @param string[] $output
     *
     * @psalm-suppress ReferenceConstraintViolation
     *
     * @return int The exit code of the command
     */
    protected function execute(
        string $binary,
        array $arguments,
        array &$output = []
    ): int {
        $arguments = array_filter($arguments, static function ($argument): bool {
            return $argument !== '';
        });

        $command = array_merge([$binary], $arguments);

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('Executing: ' . implode(' ', $command), Output::OUTPUT_RAW);
        }

        $process = new Process($command);
        $process->setTimeout(null);

        if ($this->output->isDebug()) {
            return $process->run(function (string $type, string $buffer) use (&$output): void {
                $this->output->write($buffer);
                $output[] = $buffer;
            });
        }

        $isInteractive = $this->input !== null && $this->input->isInteractive();

        $progress = $this->createProgressBar($isInteractive);
        $progress->start();

        $process->start(static function (string $type, string $buffer) use (&$output): void {
            $output[] = $buffer;
        });

        while ($process->isRunning()) {
            if ($isInteractive) {
                $progress->setProgressCharacter(self::CHARS[$progress->getProgress() % 8]);
            }

            $progress->advance();

            // Create two rotations per second
            usleep((int) ((1 / 8 / 2) * 1000000));
        }

        $progress->clear();

        if (! $isInteractive) {
            $this->output->writeln('');
        }

        return (int) $process->getExitCode();
    }

    protected static function vendorBinary(string $binary): string
    {
        $binary = PHPCSTD_BINARY_PATH . $binary;

        if (! is_file($binary)) {
            $binary = "${binary}.phar";
        }

        if (Cli::isOnWindows()) {
            $binary = "{$binary}.bat";
        }

        return $binary;
    }

    protected function createTempReportFile(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), $this->name);

        if ($tmpFile === false) {
            throw new RuntimeException('Unable to create temporary report file');
        }

        $tmpFileJson = "${tmpFile}.json";

        if (! rename($tmpFile, $tmpFileJson)) {
            throw new RuntimeException('Unable to rename temporary report file');
        }

        return $tmpFileJson;
    }

    /**
     * @return mixed[]
     */
    protected static function parseJson($raw): array
    {
        if (! is_string($raw)) {
            return [];
        }

        // Clean up malformed JSON output
        $matches = [];
        preg_match('/((?:{.*})|(?:\[.*\]))\s*$/msS', $raw, $matches);

        $json = json_decode($matches[1] ?? $raw, true, 512);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return [];
    }

    protected function createProgressBar(bool $isInteractive): ProgressBar
    {
        $progress = new ProgressBar($this->output);
        $progress->setMessage($this->getName());
        $progress->setBarWidth(1);
        $progress->setFormat('%bar% %message% (elapsed: %elapsed:6s%)');

        if ($isInteractive) {
            $progress->setRedrawFrequency(1);
            $progress->setBarCharacter(self::CHARS[0]);

            return $progress;
        }

        $progress->minSecondsBetweenRedraws(5);
        $progress->maxSecondsBetweenRedraws(5);
        $progress->setOverwrite(false);

        return $progress;
    }
}
