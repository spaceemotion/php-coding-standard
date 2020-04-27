<?php

declare(strict_types=1);

(static function () {
    // Find root "bin" folder for composer installation
    $autoloadFile = 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

    foreach (
        [
            __DIR__ . DIRECTORY_SEPARATOR . '..',
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..',
        ] as $path
    ) {
        if (file_exists($path . DIRECTORY_SEPARATOR . $autoloadFile)) {
            define('PHPCSTD_ROOT', $path . DIRECTORY_SEPARATOR);

            define(
                'PHPCSTD_BINARY_PATH',
                $path
                . DIRECTORY_SEPARATOR . 'vendor'
                . DIRECTORY_SEPARATOR . 'bin'
                . DIRECTORY_SEPARATOR,
            );

            break;
        }
    }

    if (!defined('PHPCSTD_ROOT')) {
        fwrite(STDERR, 'Vendor folder not found. Did you forget to run "composer install"?' . PHP_EOL);
        exit(1);
    }

    require_once __DIR__ . '/../vendor/autoload.php';
})();
