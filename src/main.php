<?php

declare(strict_types=1);

require_once 'cli.php';
require_once 'init.php';
require_once 'run.php';
require_once 'tools.php';

// Execute stuff
foreach ($tools as $callback) {
    if (!$callback() && !$flags['continue']) {
        exit(1);
    }
}
