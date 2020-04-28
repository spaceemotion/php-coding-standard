<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

class ConsoleFormatter implements Formatter
{
    private const COLORS = [
        'green' => '0;32',
        'gray' => '1;30',
        'red' => '0;31',
        'yellow' => '1;33',
    ];

    private const COLOR_BY_SEVERITY = [
        Violation::SEVERITY_ERROR => 'red',
        Violation::SEVERITY_WARNING => 'yellow',
    ];

    public function format(Result $result): void
    {
        $counts = [
            Violation::SEVERITY_WARNING => 0,
            Violation::SEVERITY_ERROR => 0,
        ];

        foreach ($result->files as $path => $file) {
            echo PHP_EOL . self::colorize('green', "- ${path}") . PHP_EOL;

            foreach ($file->violations as $idx => $violation) {
                $counts[$violation->severity]++;

                $severity = self::colorize(
                    self::COLOR_BY_SEVERITY[$violation->severity],
                    strtoupper($violation->severity),
                );

                $tool = self::colorize('gray', "({$violation->tool})");

                echo "  {$violation->line}: [{$severity}] {$violation->message} ${tool}" . PHP_EOL;

                if ($violation->source === '') {
                    continue;
                }

                echo str_repeat(' ', strlen("  {$violation->line}: [{$violation->severity}] "))
                    . self::colorize('gray', $violation->source)
                    . PHP_EOL;

                if ($idx < count($file->violations) - 1) {
                    echo PHP_EOL;
                }
            }
        }

        echo PHP_EOL . 'Results: ' . implode(', ', array_map(
            static fn (int $count, string $key) => "${count} ${key}(s)",
            $counts,
            array_keys($counts),
        )) . PHP_EOL;
    }

    private static function colorize(string $color, string $text): string
    {
        return "\033[" . self::COLORS[$color] . 'm' . $text . "\033[0m";
    }
}
