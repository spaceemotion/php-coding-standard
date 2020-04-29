<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

class Psalm extends Tool
{
    protected $name = 'psalm';

    public function run(Context $context): bool
    {
        $tmpFile = tempnam(sys_get_temp_dir(), $this->name);
        $tmpFileJson = "${tmpFile}.json";

        rename($tmpFile, $tmpFileJson);

        $output = [];

        if ($this->execute(self::vendorBinary($this->name), array_merge(
            $context->isFixing ? ['--alter', '--issues=all'] : [],
            [
                '--no-progress',
                '--monochrome',
                "--report=$tmpFileJson",
            ],
            $context->files
        ), $output) === 0) {
            return true;
        }

        $json = self::parseJson(file_get_contents($tmpFileJson));

        if (count($json) === 0) {
            echo implode("\n", $output) . ' ';
            return false;
        }

        foreach ($json as $entry) {
            $violation = new Violation();
            $violation->message = $entry['message'];
            $violation->tool = $this->name;
            $violation->line = $entry['line_from'];
            $violation->source = "${entry['type']} (${entry['link']})";
            $violation->severity = $entry['severity'];

            $file = new File();
            $file->violations[] = $violation;

            $result = new Result();
            $result->files[$entry['file_path']] = $file;

            $context->addResult($result);
        }

        return false;
    }
}
