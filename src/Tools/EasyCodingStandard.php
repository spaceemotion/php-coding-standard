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

        if ($this->execute($this->name, array_merge(
            [
                'check',
                '--no-progress-bar',
                '--output-format=json',
            ],
            $context->isFixing ? ['--fix'] : [],
            $context->files
        ), $output) === 0) {
            return true;
        }

        $json = self::parseJson(implode('', $output));

        if ($json === []) {
            return false;
        }

        $result = new Result();

        foreach ($json['files'] as $path => $details) {
            $file = new File();

            foreach (($details['errors'] ?? []) as $error) {
                $violation = new Violation();
                $violation->line = $error['line'];
                $violation->message = $error['message'];
                $violation->source = $error['sourceClass'];
                $violation->tool = $this->name;

                $file->violations[] = $violation;
            }

            if (count($file->violations) > 0) {
                $result->files[$path] = $file;
            }
        }

        $context->addResult($result);

        return false;
    }
}
