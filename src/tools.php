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
    ];
