<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

class ConsoleFormatter implements Formatter
{
    private const COLORS = [
        'green' => '0;32',
        'gray' => '0;37',
        'red' => '0;31',
        'yellow' => '1;33',
        'blue' => '0;34',
    ];

    private const COLOR_BY_SEVERITY = [
        Violation::SEVERITY_ERROR => 'red',
        Violation::SEVERITY_WARNING => 'yellow',
        Violation::SEVERITY_INFO => 'blue',
    ];

    public function format(Result $result): void
    {
        $counts = [
            Violation::SEVERITY_WARNING => 0,
            Violation::SEVERITY_ERROR => 0,
            Violation::SEVERITY_INFO => 0,
        ];

        foreach ($result->files as $path => $file) {
            echo PHP_EOL . self::colorize('green', $path) . PHP_EOL;

            $violationsSorted = self::sortByLineNumber($file->violations);

            foreach ($violationsSorted as $idx => $violation) {
                $counts[$violation->severity]++;

                $severity = self::colorize(
                    self::COLOR_BY_SEVERITY[$violation->severity],
                    strtoupper($violation->severity)
                );

                $tool = self::colorize('gray', "({$violation->tool})");

                echo "  {$violation->line}: [{$severity}] {$violation->message} ${tool}" . PHP_EOL;

                if ($violation->source === '') {
                    continue;
                }

                echo str_repeat(' ', strlen("  {$violation->line}: [{$violation->severity}] "))
                    . self::colorize('gray', $violation->source)
                    . PHP_EOL;

                if ($idx < count($violationsSorted) - 1) {
                    echo PHP_EOL;
                }
            }
        }

        echo PHP_EOL . 'Results: ' . implode(', ', array_map(
            static function (int $count, string $key): string {
                return "${count} ${key}(s)";
            },
            $counts,
            array_keys($counts)
        )) . PHP_EOL;
    }

    private static function colorize(string $color, string $text): string
    {
        return "\033[" . self::COLORS[$color] . 'm' . $text . "\033[0m";
    }

    /**
     * @param Violation[] $violations
     *
     * @return Violation[]
     *
     * @psalm-return list<Violation>
     */
    private static function sortByLineNumber(array $violations): array
    {
        uasort(
            $violations,
            static function (Violation $first, Violation $second): int {
                return $first->line <=> $second->line;
            }
        );

        return array_values($violations);
    }
}
