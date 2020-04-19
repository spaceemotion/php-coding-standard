<?php

declare(strict_types=1);

/**
 * Runs the given command list in sequence.
 * On windows, the .bat file will be executed instead.
 *
 * @param string $command The raw binary name
 * @param string[] $arguments
 */
function run(string $command, array $arguments): int
{
    $arguments = implode(' ', $arguments);

    $binary = PHPCSTD_ROOT . $command;

    if (PHP_OS_FAMILY === 'Windows') {
        $binary = "{$binary}.bat";
    }

    echo "-> {$command} {$arguments}" . PHP_EOL;

    $exitCode = 0;

    passthru("{$binary} {$arguments}", $exitCode);

    return $exitCode;
}
