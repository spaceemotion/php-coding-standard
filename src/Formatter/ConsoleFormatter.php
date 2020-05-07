<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

use Spaceemotion\PhpCodingStandard\Cli;

class ConsoleFormatter implements Formatter
{
    protected const COLORS = [
        'green' => '0;32',
        'gray' => '0;37',
        'red' => '0;31',
        'yellow' => '1;33',
        'blue' => '0;34',
    ];

    protected const COLOR_BY_SEVERITY = [
        Violation::SEVERITY_ERROR => 'red',
        Violation::SEVERITY_WARNING => 'yellow',
        Violation::SEVERITY_INFO => 'blue',
    ];

    /** @var bool */
    protected $supportsColor = false;

    public function __construct()
    {
        $this->supportsColor = $this->hasColorSupport();
    }

    public function format(Result $result): void
    {
        $counts = [
            Violation::SEVERITY_WARNING => 0,
            Violation::SEVERITY_ERROR => 0,
            Violation::SEVERITY_INFO => 0,
        ];

        foreach ($result->files as $path => $file) {
            echo "\n" . $this->colorize('green', $path) . "\n";

            $violationsSorted = self::sortByLineNumber($file->violations);

            foreach ($violationsSorted as $idx => $violation) {
                $counts[$violation->severity]++;

                $severity = $this->colorize(
                    self::COLOR_BY_SEVERITY[$violation->severity],
                    strtoupper($violation->severity)
                );

                $tool = $this->colorize('gray', "({$violation->tool})");

                echo "  {$violation->line}: [{$severity}] {$violation->message} ${tool}" . "\n";

                if ($violation->source === '') {
                    continue;
                }

                $perLinePrefix = str_repeat(' ', strlen("  {$violation->line}: [{$violation->severity}] "));

                echo implode(
                    "\n",
                    array_map(
                        function (string $line) use ($perLinePrefix): string {
                            return $perLinePrefix . $this->colorize('gray', $line);
                        },
                        explode("\n", $violation->source)
                    )
                ) . "\n";

                if ($idx < count($violationsSorted) - 1) {
                    echo "\n";
                }
            }
        }

        echo "\n" . 'Results: ' . implode(', ', array_map(
            static function (int $count, string $key): string {
                return "${count} ${key}(s)";
            },
            $counts,
            array_keys($counts)
        )) . "\n";
    }

    protected function colorize(string $color, string $text): string
    {
        if (! $this->supportsColor) {
            return $text;
        }

        return "\033[" . self::COLORS[$color] . 'm' . $text . "\033[0m";
    }

    protected function isTty(): bool
    {
        if (getenv('TERM_PROGRAM') === 'Hyper') {
            return true;
        }

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return (function_exists('sapi_windows_vt100_support')
                    && sapi_windows_vt100_support(STDIN))
                || getenv('ANSICON') !== false
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm';
        }

        if (function_exists('stream_isatty')) {
            return stream_isatty(STDIN);
        }

        return false;
    }

    protected function hasColorSupport(): bool
    {
        // Follow https://no-color.org/ as symfony does the same
        // https://github.com/symfony/Console/blob/master/Output/StreamOutput.php#L94
        if (isset($_SERVER['NO_COLOR']) || getenv('NO_COLOR') !== false) {
            return false;
        }

        if (in_array('--' . Cli::FLAG_ANSI, $_SERVER['argv'], true)) {
            return true;
        }

        return $this->isTty();
    }

    /**
     * @param Violation[] $violations
     *
     * @return Violation[]
     *
     * @psalm-return list<Violation>
     */
    protected static function sortByLineNumber(array $violations): array
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
