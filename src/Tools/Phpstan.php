<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Closure;
use Spaceemotion\PhpCodingStandard\Cli;
use Spaceemotion\PhpCodingStandard\Config;
use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;
use Spaceemotion\PhpCodingStandard\ProgressTracker;

class Phpstan extends Tool
{
    /** @var string */
    protected $name = 'phpstan';

    public function run(Context $context): bool
    {
        $ignoreSources = (bool) ($context->config->getPart($this->getName())[Config::IGNORE_SOURCES] ?? false);
        $files = $ignoreSources ? [] : $context->files;

        $output = [];

        if (
            $this->execute(self::vendorBinary($this->name), array_merge(
                [
                    'analyse',
                    '--error-format=json',
                    '--no-ansi',
                    '--no-interaction',
                ],
                $files
            ), $output, (
                $context->fast ? null : new ProgressTracker(
                    Closure::fromCallable([$this, 'trackProgress']),
                    ['--debug']
                )
            )) === 0
        ) {
            return true;
        }

        $lastLine = $output[count($output) - 1];
        $json = self::parseJson($lastLine);
        $result = new Result();

        if ($json === []) {
            $match = [];

            if (preg_match('/(.*) in (.*?) on line (\d+)$/i', $lastLine, $match) !== 1) {
                $violation = new Violation();
                $violation->message = trim($lastLine);
                $violation->tool = $this->getName();

                $file = new File();
                $file->violations[] = $violation;
                $result->files[File::GLOBAL] = $file;
                $context->addResult($result);

                return false;
            }

            $file = new File();

            $violation = new Violation();
            $violation->line = (int) $match[3];
            $violation->message = $match[1];
            $violation->tool = $this->getName();

            $file->violations[] = $violation;

            $result->files[$match[2]] = $file;

            $context->addResult($result);

            return false;
        }

        foreach ($json['files'] as $filename => $details) {
            $file = new File();

            foreach ($details['messages'] as $message) {
                $violation = new Violation();
                $violation->line = (int) ($message['line'] ?? 0);
                $violation->message = $message['message'];
                $violation->tool = $this->name;

                $file->violations[] = $violation;
            }

            $result->files[$filename] = $file;
        }

        $context->addResult($result);

        return false;
    }

    protected function trackProgress(string $line): bool
    {
        $firstLetter = $line[0] ?? '';

        if (Cli::isOnWindows()) {
            // TODO how are file paths on windows again?
            return $firstLetter !== '{';
        }

        return $firstLetter === '/';
    }
}
