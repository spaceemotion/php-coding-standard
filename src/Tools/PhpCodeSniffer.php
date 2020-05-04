<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

class PhpCodeSniffer extends Tool
{
    protected $name = 'php_codesniffer';

    public function run(Context $context): bool
    {
        $output = [];

        if (
            $this->execute(
                self::vendorBinary($context->isFixing ? 'phpcbf' : 'phpcs'),
                array_merge([
                    '--report=json',
                    '-v', // prints out every file it parses, use this for progress tracking
                ], $context->files),
                $output,
                [$this, 'trackProgress']
            ) === 0
        ) {
            return true;
        }

        $json = self::parseJson($output[count($output) - 1]);
        $result = new Result();

        foreach (($json['files'] ?? []) as $fileName => $details) {
            $messages = $details['messages'] ?? [];

            if (count($messages) === 0) {
                continue;
            }

            $file = new File();

            foreach ($messages as $message) {
                $violation = new Violation();
                $violation->line = (int) $message['line'];
                $violation->message = $message['message'];
                $violation->severity = strtolower($message['type']);
                $violation->source = $message['source'];
                $violation->tool = $this->name;

                $file->violations[] = $violation;
            }

            $result->files[$fileName] = $file;
        }

        $context->result->add($result);

        // Return true as long as we can fix it
        if (! $context->isFixing) {
            return false;
        }

        $totals = $json['totals'] ?? [];

        return ($totals['errors'] ?? 0) >= ($totals['fixable'] ?? 0);
    }

    protected function trackProgress(string $line): bool
    {
        return stripos($line, 'processing') === 0;
    }
}
