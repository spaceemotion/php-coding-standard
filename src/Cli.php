<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use function stream_set_blocking;

use const STDIN;

class Cli
{
    public const FLAG_CI = 'ci';

    public const FLAG_CONTINUE = 'continue';

    public const FLAG_FIX = 'fix';

    public const FLAG_ANSI = 'ansi';

    public const FLAG_HIDE_SOURCE = 'hide-source';

    public const FLAG_LINT_STAGED = 'lint-staged';

    public const FLAG_NO_FAIL = 'no-fail';

    public const PARAMETER_SKIP = 'skip';

    public const PARAMETER_ONLY = 'only';

    public static function isOnWindows(): bool
    {
        if (defined('PHP_OS_FAMILY')) {
            return PHP_OS_FAMILY === 'Windows';
        }

        return stripos(PHP_OS, 'WIN') === 0;
    }

    /**
     * @return string[]
     */
    public static function parseFilesFromInput(): array
    {
        // Windows does not support nonblocking input streams:
        // https://bugs.php.net/bug.php?id=34972
        if (self::isOnWindows()) {
            return [];
        }

        // Don't block execution if we don't have any piped input
        stream_set_blocking(STDIN, false);

        // Read each file path per line from the input
        $files = [];

        while (($file = fgets(STDIN)) !== false) {
            $files[] = trim($file);
        }

        stream_set_blocking(STDIN, true);

        return $files;
    }
}
