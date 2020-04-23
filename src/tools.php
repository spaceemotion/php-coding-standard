<?php

declare(strict_types=1);

// Define all possible commands
$tools = $flags['fix']
    ? [
        static fn () => (
            run('ecs', [
                'check',
                ...$files,
                '--output-format=json',
                '--fix',
            ]) === 0
        ),
    ]
    : [
        static fn () => (
            run('ecs', [
                'check',
                ...$files,
                $flags['ci']
                    ? '--no-progress-bar'
                    : '',
                '--output-format=json',
            ]) < 2
        ),
        static fn () => (
            run('phpstan', [
                'analyse',
                ...$files,
                $flags['ci']
                    ? '--no-ansi --error-format=checkstyle'
                    : '--ansi --error-format=json',
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
