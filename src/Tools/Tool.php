<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use RuntimeException;
use Spaceemotion\PhpCodingStandard\Cli;
use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\ProgressOutput;

abstract class Tool
{
    /** @var string A short name for the tool to be used in the config */
    protected $name = '';

    /**
     * Runs this tool with the given context.
     */
    abstract public function run(Context $context): bool;

    /**
     * Indicates if this should should run for the given context.
     */
    public function shouldRun(Context $context): bool
    {
        if (! $context->config->isEnabled($this->name)) {
            return false;
        }

        if (in_array(static::class, $context->toolsExecuted, true)) {
            return false;
        }

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
        array &$output = [],
        ?callable $progressTracker = null
    ): int {
        $arguments = array_filter($arguments, static function ($argument): bool {
            return $argument !== '';
        });

        $arguments = array_map('escapeshellarg', $arguments);
        $joined = implode(' ', $arguments);

        $command = "{$binary} {$joined} 2>&1";
        $exitCode = 0;

        if ($progressTracker === null) {
            // We don't support a progress bar
            exec($command, $output, $exitCode);

            return $exitCode;
        }

        // Let the tool read each line of the output
        $progress = new ProgressOutput();
        $handle = popen($command, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open process: ${command}");
        }

        while (! feof($handle)) {
            $read = fgets($handle);

            if (! is_string($read)) {
                continue;
            }

            $output[] = $read;

            // Show little dots whenever this returns true
            if ($progressTracker($read)) {
                $progress->advance();
            }
        }

        return pclose($handle);
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

    /**
     * @return mixed[]
     */
    protected static function parseJson(string $raw): array
    {
        // Clean up malformed JSON output
        $matches = [];
        preg_match('/((?:{.*})|(?:\[.*\]))\s*$/msS', $raw, $matches);

        $json = json_decode($matches[1] ?? $raw, true, 512);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return [];
    }
}
