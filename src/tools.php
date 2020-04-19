<?php

declare(strict_types=1);

// Define all possible commands
$tools = [
        static fn () => (
            run('phpstan', [
                'analyse',
                ...$files,
                $flags['ci'] ? '--no-ansi --error-format=checkstyle' : '--ansi',
            ]) === 0
        ),
        static fn () => (
            run('phpmd', [
                implode(',', $files),
                $flags['ci'] ? 'xml' : 'ansi',
                'phpmd.xml',
            ]) === 0
        ),
    ];
