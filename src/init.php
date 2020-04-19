<?php

declare(strict_types=1);

// Find root "bin" folder for composer installation
$autoloadFile = 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

foreach (
    [
        __DIR__ . DIRECTORY_SEPARATOR . '..',
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..',
    ] as $path
) {
    if (file_exists($path . DIRECTORY_SEPARATOR . $autoloadFile)) {
        define(
            'PHLINT_ROOT',
            $path
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'bin'
            . DIRECTORY_SEPARATOR,
        );

        echo 'Running in ' . PHLINT_ROOT . PHP_EOL;

        break;
    }
}

if (!defined('PHLINT_ROOT')) {
    fwrite(STDERR, 'Vendor folder not found. Did you forget to run "composer install"?');
    exit(1);
}
