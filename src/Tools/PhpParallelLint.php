<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

class PhpParallelLint extends Tool
{
    protected $name = 'parallel-lint';

    public function run(Context $context): bool
    {
        $output = [];

        if ($this->execute($this->name, [
            '--no-progress',
            ...$context->files,
            '--json',
        ], $output) === 0) {
            return true;
        }

        $json = json_decode($output[count($output) - 1], true, 512);

        foreach ($json['results']['errors'] as $error) {
            $message = self::removeFromEnd(
                $error['normalizeMessage'],
                " in {$error['file']} on line {$error['line']}"
            );

            $violation = new Violation();
            $violation->line = $error['line'];
            $violation->tool = $this->name;
            $violation->message = $message;

            $file = new File();
            $file->violations[] = $violation;

            $result = new Result();
            $result->files[$error['file']] = $file;
            $context->addResult($result);
        }

        return false;
    }

    public function shouldRun(Context $context): bool
    {
        if (! $context->config->isEnabled($this->name)) {
            return false;
        }

        if (in_array(static::class, $context->toolsExecuted, true)) {
            return false;
        }

        return true;
    }

    private static function removeFromEnd(string $string, string $end): string
    {
        if (substr($string, -strlen($end)) !== $end) {
            return $string;
        }

        return substr($string, 0, -strlen($end));
    }
}
