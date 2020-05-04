<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

class EasyCodingStandard extends Tool
{
    protected $name = 'ecs';

    public function run(Context $context): bool
    {
        $output = [];

        $this->execute(self::vendorBinary($this->name), array_merge(
            [
                'check',
                '--no-progress-bar',
                '--output-format=json',
            ],
            $context->isFixing ? ['--fix'] : [],
            $context->files
        ), $output);

        $json = self::parseJson(implode('', $output));

        if ($json === []) {
            return false;
        }

        $result = new Result();

        foreach (($json['files'] ?? []) as $path => $details) {
            $file = new File();

            foreach (($details['errors'] ?? []) as $error) {
                $violation = new Violation();
                $violation->line = $error['line'];
                $violation->message = $error['message'];
                $violation->source = $error['sourceClass'];
                $violation->tool = $this->name;

                $file->violations[] = $violation;
            }

            if (! $context->isFixing) {
                foreach (($details['diffs'] ?? []) as $diff) {
                    $matches = [];

                    preg_match_all(
                        '/^@@ -(\d+),(\d+) \+(\d+),(\d+) @@(.+?)(?=@@|\Z)/ms',
                        $diff['diff'],
                        $matches,
                        PREG_SET_ORDER
                    );

                    foreach ($matches as $match) {
                        // 0 = all
                        // 1 = from line number
                        // 2 = from length
                        // 3 = to line number
                        // 4 = to length
                        // 5 = diff excerpt

                        $fromLineNumber = (int) $match[1];

                        $violation = new Violation();
                        $violation->line = $fromLineNumber;
                        $violation->message = 'Styling issues found';
                        $violation->tool = $this->name;
                        $violation->source = rtrim($match[0], "\n\r");

                        $file->violations[] = $violation;
                    }
                }
            }

            if (count($file->violations) > 0) {
                $result->files[$path] = $file;
            }
        }

        $context->addResult($result);

        return count($result->files) === 0;
    }
}
