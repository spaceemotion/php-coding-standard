<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Style\SymfonyStyle;

use function strip_tags;

class GithubActionFormatter extends ConsoleFormatter
{
    public function format(Result $result, SymfonyStyle $style): void
    {
        foreach ($result->files as $fileName => $file) {
            $fullPath = PHPCSTD_ROOT . $fileName;
            $violations = [];

            foreach ($file->violations as $violation) {
                $type = strtolower($violation->severity);
                $violations[$violation->line][$type][] = "{$violation->message} ({$violation->tool})";
            }

            $style->writeln("::group::{$fileName}", Output::OUTPUT_RAW);

            foreach ($violations as $line => $byType) {
                foreach ($byType as $type => $violation) {
                    // Replace NL with url-encoded %0A so they show up
                    $violation = trim(strip_tags(implode("\n", $violation)));
                    $violation = str_replace("\n", '%0A', $violation);

                    $style->writeln(
                        "::{$type} file={$fullPath},line={$line}::{$violation}",
                        Output::OUTPUT_RAW
                    );
                }
            }

            $style->writeln('::endgroup::', Output::OUTPUT_RAW);
        }
    }
}
