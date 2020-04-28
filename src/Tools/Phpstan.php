<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

class Phpstan extends Tool
{
    protected $name = 'phpstan';

    public function run(Context $context): bool
    {
        $output = [];

        if ($this->execute($this->name, array_merge(
            [
                'analyse',
                '--error-format=json',
                '--no-progress',
                '--no-ansi',
                '--no-interaction',
            ],
            $context->files
        ), $output) === 0) {
            return true;
        }

        $lastLine = $output[count($output) - 1];
        $json = self::parseJson($lastLine);
        $result = new Result();

        if ($json === []) {
            return false;
        }

        foreach ($json['files'] as $filename => $details) {
            $file = new File();

            foreach ($details['messages'] as $message) {
                $violation = new Violation();
                $violation->line = $message['line'];
                $violation->message = $message['message'];
                $violation->tool = $this->name;

                $file->violations[] = $violation;
            }

            $result->files[$filename] = $file;
        }

        $context->addResult($result);

        return false;
    }
}
