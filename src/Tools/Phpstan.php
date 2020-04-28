<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

class Phpstan extends Tool
{
    protected string $name = 'phpstan';

    public function run(Context $context): bool
    {
        $output = [];

        if ($this->execute($this->name, [
            'analyse',
            ...$context->files,
            '--error-format=json',
            '--no-progress',
            '--no-ansi',
            '--no-interaction',
        ], $output) === 0) {
            return true;
        }

        $json = json_decode($output[count($output) - 1], true, 512, JSON_THROW_ON_ERROR);

        $result = new Result();

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
