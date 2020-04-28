<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;

abstract class Tool
{
    /** @var bool Indicates whether this tool can auto-fix any violations it finds */
    protected $canFix = false;

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

        if ($context->isFixing) {
            return $this->canFix;
        }

        return true;
    }

    /**
     * Runs the given command list in sequence.
     * On windows, the .bat file will be executed instead.
     *
     * @param string $command The raw binary name
     * @param string[] $arguments
     *
     * @return int The exit code of the command
     */
    protected function execute(string $command, array $arguments, ?array &$output = null): int
    {
        $arguments = array_map('escapeshellarg', $arguments);
        $joined = implode(' ', $arguments);

        $binary = PHPCSTD_BINARY_PATH . $command;

        if (PHP_OS_FAMILY === 'Windows') {
            $binary = "{$binary}.bat";
        }

        echo "-> {$command} {$joined}" . PHP_EOL;

        $exitCode = 0;

        if ($output !== null) {
            exec("{$binary} {$joined} 2>&1", $output, $exitCode);

            return $exitCode;
        }

        passthru("{$binary} {$joined}", $exitCode);

        return $exitCode;
    }

    protected static function parseJson(string $raw): array
    {
        $json = json_decode($raw, true, 512);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return [];
    }
}
