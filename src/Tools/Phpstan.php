<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Config;
use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

use function preg_match;

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
            $this->execute(self::vendorBinary($this->getName()), array_merge(
                [
                    'analyse',
                    '--error-format=json',
                    '--no-ansi',
                    '--no-interaction',
                    '--no-progress',
                ],
                $files
            ), $output) === 0
        ) {
            return true;
        }

        $json = self::parseJson($this->getJsonLine($output));
        $result = new Result();

        $globalFile = new File();
        $result->files[File::GLOBAL] = $globalFile;

        if ($json === []) {
            $message = trim(implode("\n", $output));
            $match = [];

            if (preg_match('/(.*) in (.*?) on line (\d+)$/i', $message, $match) !== 1) {
                $violation = new Violation();
                $violation->message = $message;
                $violation->tool = $this->getName();

                $globalFile->violations[] = $violation;
                $context->addResult($result);

                return false;
            }

            $violation = new Violation();
            $violation->line = (int) $match[3];
            $violation->message = $match[1];
            $violation->tool = $this->getName();

            $file = new File();
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
                $violation->tool = $this->getName();

                $file->violations[] = $violation;
            }

            $result->files[$filename] = $file;
        }

        foreach ($json['errors'] as $error) {
            $violation = new Violation();
            $violation->message = $error;
            $violation->tool = $this->getName();

            $globalFile->violations[] = $violation;
        }

        $context->addResult($result);

        return false;
    }

    private function getJsonLine(array $output): string
    {
        foreach ($output as $line) {
            if (preg_match('/^{"totals/', $line) === 1) {
                return $line;
            }
        }

        return '';
    }
}
