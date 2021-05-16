<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_keys;
use function array_sum;
use function explode;
use function preg_replace;
use function str_repeat;
use function strip_tags;
use function strlen;
use function strpos;

use const STR_PAD_LEFT;
use const STR_PAD_RIGHT;

class ConsoleFormatter implements Formatter
{
    protected const COLOR_BY_SEVERITY = [
        Violation::SEVERITY_ERROR => 'fg=red',
        Violation::SEVERITY_WARNING => 'fg=yellow',
    ];

    /** @var bool */
    protected $printSource = false;

    public function __construct(bool $hideSource)
    {
        $this->printSource = ! $hideSource;
    }

    public function format(Result $result, SymfonyStyle $style): void
    {
        $counts = [
            Violation::SEVERITY_WARNING => 0,
            Violation::SEVERITY_ERROR => 0,
        ];

        $style->writeln('');

        foreach ($result->files as $path => $file) {
            $style->writeln("<info>{$path}</info>");

            foreach (self::sortByLineNumber($file->violations) as $violation) {
                $counts[$violation->severity]++;

                $severity = $this->colorize(
                    self::COLOR_BY_SEVERITY[$violation->severity],
                    strtoupper($violation->severity)
                );

                $tool = $this->colorize('fg=blue', $violation->tool);

                $message = $this->highlightClasses($violation->message);

                if ($this->printSource && $violation->source !== '') {
                    $message .= "\n<gray>{$violation->source}</gray>";
                }

                $this->writeRow($style, [
                    (string) $violation->line,
                    $severity,
                    $tool,
                    $message,
                ]);
            }

            $style->writeln('', Output::OUTPUT_RAW);
        }

        $results = implode(', ', array_map(
            static function (int $count, string $key): string {
                return "${count} ${key}(s)";
            },
            $counts,
            array_keys($counts)
        ));

        if ($counts[Violation::SEVERITY_ERROR] > 0) {
            $style->error($results);
            return;
        }

        if ($counts[Violation::SEVERITY_WARNING] > 0) {
            $style->warning($results);
            return;
        }

        $style->success($results);
    }

    protected function colorize(string $color, string $text): string
    {
        return "<{$color}>{$text}</>";
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

    /**
     * @param string[] $array
     */
    protected function writeRow(OutputInterface $output, array $array): void
    {
        $widths = [5, 8, 15, 0];
        $nlPrefix = str_repeat(' ', array_sum($widths) + count($widths) - 1);

        foreach ($array as $column => $cell) {
            foreach (explode("\n", $cell) as $idx => $line) {
                if ($widths[$column] > 0) {
                    $rawLength = $widths[$column] + strlen($line) - strlen(strip_tags($line));
                    $line = str_pad($line, $rawLength, ' ', $column > 1 ? STR_PAD_RIGHT : STR_PAD_LEFT);
                }

                $output->write(($idx > 0 ? "\n{$nlPrefix}" : '') . $line . ' ');
            }
        }

        $output->write(PHP_EOL);
    }

    protected function highlightClasses(string $message): string
    {
        // Only highlight text when no tags exist yet
        if (strpos($message, '</') !== false) {
            return $message;
        }

        // Find variables
        $message = preg_replace('/\$\w+/S', '<fg=cyan>$0</>', $message) ?? $message;

        // Find classes/statics/const
        return (string) preg_replace(
            '/\\\\?[A-Za-z]+\\\\[A-Za-z\\\]+(::[a-zA-Z]+(\(\))?)?/S',
            '<fg=magenta>$0</>',
            $message
        );
    }
}
