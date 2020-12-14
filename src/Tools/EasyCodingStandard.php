<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Closure;
use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;
use Spaceemotion\PhpCodingStandard\ProgressTracker;

use function implode;
use function preg_match_all;
use function rtrim;

use const PREG_SET_ORDER;

class EasyCodingStandard extends Tool
{
    /** @var string */
    protected $name = 'ecs';

    public function run(Context $context): bool
    {
        $output = [];

        $this->execute(self::vendorBinary($this->name), array_merge(
            [
                'check',
                '--output-format=json',
            ],
            $context->isFixing ? ['--fix'] : [],
            $context->files
        ), $output, $context->fast ? null : (
            new ProgressTracker(Closure::fromCallable([$this, 'trackProgress']), [
                '--debug',
            ])
        ));

        $outputText = implode('', $output);
        $json = self::parseJson(substr($outputText, (int) strpos($outputText, '{')));

        if ($json === []) {
            return false;
        }

        $result = $this->parseResult($json['files'] ?? [], $context);

        $context->addResult($result);

        return count($result->files) === 0;
    }

    /**
     * @param mixed[] $files
     */
    protected function parseResult(array $files, Context $context): Result
    {
        $result = new Result();

        foreach ($files as $path => $details) {
            $file = new File();

            foreach (($details['errors'] ?? []) as $error) {
                $violation = new Violation();
                $violation->line = $error['line'];
                $violation->message = $error['message'];
                $violation->source = $error['source_class'];
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
                        $violation->source = implode("\n", $diff['applied_checkers'])
                            . "\n\n"
                            . rtrim($match[0], "\n\r");

                        $file->violations[] = $violation;
                    }
                }
            }

            if (count($file->violations) > 0) {
                $result->files[$path] = $file;
            }
        }

        return $result;
    }

    protected function trackProgress(string $line): bool
    {
        return stripos(ltrim($line), '[file]') === 0;
    }
}
