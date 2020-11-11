<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

class GithubActionFormatter extends ConsoleFormatter
{
    public function format(Result $result): void
    {
        foreach ($result->files as $fileName => $file) {
            $fullPath = PHPCSTD_ROOT . $fileName;
            $violations = [];

            foreach ($file->violations as $violation) {
                $type = strtolower($violation->severity);
                $violations[$violation->line][$type][] = "{$violation->message} ({$violation->tool})";
            }

            echo "::group::{$fileName}\n";

            foreach ($violations as $line => $byType) {
                foreach ($byType as $type => $violation) {
                    // Replace NL with url-encoded %0A so they show up
                    $violation = str_replace("\n", '%0A', trim(implode("\n", $violation)));
                    echo "::{$type} file={$fullPath},line={$line}::{$violation}\n";
                }
            }

            echo "::endgroup::\n";
        }
    }
}
