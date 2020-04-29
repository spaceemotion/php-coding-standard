<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;

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
    protected function execute(string $binary, array $arguments, array &$output = []): int
    {
        $arguments = array_filter($arguments, static function ($argument): bool {
            return $argument !== '';
        });

        $arguments = array_map('escapeshellarg', $arguments);
        $joined = implode(' ', $arguments);

        $exitCode = 0;

        exec("{$binary} {$joined} 2>&1", $output, $exitCode);

        return $exitCode;
    }

    protected static function vendorBinary(string $binary): string
    {
        $binary = PHPCSTD_BINARY_PATH . $binary;

        if (PHP_OS_FAMILY === 'Windows') {
            $binary = "{$binary}.bat";
        }

        if (! is_file($binary)) {
            $binary = "${binary}.phar";
        }

        return $binary;
    }

    /**
     * @return mixed[]
     */
    protected static function parseJson(string $raw): array
    {
        $json = json_decode($raw, true, 512);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return [];
    }
}
