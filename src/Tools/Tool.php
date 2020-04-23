<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;

abstract class Tool
{
    abstract public function run(Context $context): bool;

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
