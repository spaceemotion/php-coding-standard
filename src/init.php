<?php

declare(strict_types=1);

(static function (): void {
    // Find root "bin" folder for composer installation
    static $autoloadFile = 'vendor/autoload.php';

    for (
        $path = __DIR__ . '/', $level = 0;
        $level <= 3;
        $path .= '/..', $level++
    ) {
        if (file_exists("{$path}/{$autoloadFile}")) {
            define('PHPCSTD_ROOT', "{$path}/");
            define('PHPCSTD_BINARY_PATH', PHPCSTD_ROOT . 'vendor/bin/');

            require_once "{$path}/vendor/autoload.php";

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
    (new \Composer\XdebugHandler\XdebugHandler('phpcstd'))->check();
})();
