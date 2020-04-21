<?php

declare(strict_types=1);

// Build cli options
static $help = [
    'ci' => 'Changes the output format to checkstyle.xml for better CI integration',
    'fix' => 'Try to fix any linting errors (disables other tools)',
    'continue' => 'Just run the next check if the previous one failed',
    'help' => 'Displays this help message',
];

$options = getopt('', array_keys($help));
$flags = array_combine(
    array_keys($help),
    array_map(
        static fn ($_, $flag) => array_key_exists($flag, $options),
        $help,
        array_keys($help),
    ),
);

if ($flags['help']) {
    echo 'Usage:' . PHP_EOL;
    echo '  phpcstd [options] <files or folders>' . PHP_EOL . PHP_EOL;

    echo 'Options:' . PHP_EOL;

    foreach ($help as $flag => $message) {
        echo "  --{$flag}" . PHP_EOL . "    $message" . PHP_EOL . PHP_EOL;
    }

    exit;
}

$files = array_slice($argv, count($options) + 1);

if (count($files) === 0) {
    echo 'No files specified.' . PHP_EOL;
    exit(1);
}
