<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

class PhpMessDetector extends Tool
{
    protected $name = 'phpmd';

    public function run(Context $context): bool
    {
        $output = [];

        if (
            $this->execute(self::vendorBinary($this->name), [
                implode(',', $context->files),
                'json',
                'phpmd.xml',
            ], $output) === 0
        ) {
            return true;
        }

        $json = self::parseJson(implode('', $output));

        if ($json === []) {
            return false;
        }

        $result = new Result();

        foreach ($json['files'] as $entry) {
            $file = new File();

            foreach ($entry['violations'] as $details) {
                $violation = new Violation();
                $violation->line = (int) $details['beginLine'];
                $violation->message = $details['description'];
                $violation->source = "{$details['ruleSet']} > {$details['rule']} ({$details['externalInfoUrl']})";
                $violation->tool = $this->name;

                $file->violations[] = $violation;
            }

            $result->files[$entry['file']] = $file;
        }

        $context->addResult($result);

        return false;
    }
}
