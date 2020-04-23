<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;

abstract class Tool
{
    /** @var bool Indicates whether this tool can auto-fix any violations it finds */
    protected bool $canFix = false;

    /**
     * Runs this tool with the given context.
     */
    abstract public function run(Context $context): bool;

    /**
     * Indicates if this should should run for the given context.
     */
    public function shouldRun(Context $context): bool
    {
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
    protected function execute(string $command, array $arguments): int
    {
        $joined = implode(' ', $arguments);

        $binary = PHPCSTD_BINARY_PATH . $command;

        if (PHP_OS_FAMILY === 'Windows') {
            $binary = "{$binary}.bat";
        }

        echo "-> {$command} {$joined}" . PHP_EOL;

        $exitCode = 0;

        passthru("{$binary} {$joined}", $exitCode);

        return $exitCode;
    }
}
