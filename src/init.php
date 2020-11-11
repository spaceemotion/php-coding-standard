<?php

// phpcs:disable PSR1.Files.SideEffects

declare(strict_types=1);

use Composer\XdebugHandler\XdebugHandler;
use Spaceemotion\PhpCodingStandard\Cli;

(static function (): void {
    // Find root "bin" folder for composer installation
    static $autoloadFile = 'vendor/autoload.php';

    for (
        $path = __DIR__, $level = 0;
        $level <= 4;
        $path .= '/..', $level++
    ) {
        if (file_exists("{$path}/{$autoloadFile}")) {
            $realPath = realpath($path);

            // @phan-suppress-current-line PhanUndeclaredVariable
            define('PHPCSTD_ROOT', "{$realPath}/");
            define('PHPCSTD_BINARY_PATH', PHPCSTD_ROOT . 'vendor/bin/');

            require_once "{$realPath}/vendor/autoload.php";

            break;
        }
    }

    if (! defined('PHPCSTD_ROOT')) {
        fwrite(STDERR, 'Vendor folder not found. Did you forget to run "composer install"?' . PHP_EOL);
        exit(1);
    }

    // Try to grab as much memory as we can
    ini_set('memory_limit', '-1');

    // Don't run with XDebug enabled to improve performance
    (new XdebugHandler('phpcstd', '--' . Cli::FLAG_ANSI))->check();
})();
