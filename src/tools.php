<?php

declare(strict_types=1);

// Define all possible commands
$tools = $flags['fix']
    ? [
        static fn () => (
            run('phpcbf', $files) === 0
        ),
    ]
    : [
        static fn () => (
            run('phpcs', [
                $flags['ci'] ? '--no-colors --report=checkstyle' : '--colors --report=summary' ,
                ...$files,
            ]) < 2
        ),
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
