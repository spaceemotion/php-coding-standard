<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

class PhpCodeSniffer extends Tool
{
    /** @var string */
    protected $name = 'php_codesniffer';

    public function run(Context $context): bool
    {
        if ($context->isFixing) {
            $this->sniff($context, 'phpcbf');
        }

        $output = [];

        if ($this->sniff($context, 'phpcs', $output) === 0) {
            return true;
        }

        $json = self::parseJson($output[(is_countable($output) ? count($output) : 0) - 1]);
        $result = new Result();

        foreach (($json['files'] ?? []) as $fileName => $details) {
            $messages = $details['messages'] ?? [];

            if ((is_countable($messages) ? count($messages) : 0) === 0) {
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

        return ($totals['errors'] ?? 0) + ($totals['warnings'] ?? 0) === ($totals['fixable'] ?? 0);
    }

    /**
     * @param string[] $output
     */
    protected function sniff(Context $context, string $binary, array &$output = []): int
    {
        $config = $context->config->getPart($this->name);

        return $this->execute(
            self::vendorBinary($binary),
            array_merge(
                [
                    '--report=json',
                    '--parallel=' . (int) ($config['processes'] ?? 24),
                ],
                $context->files
            ),
            $output
        );
    }
}
