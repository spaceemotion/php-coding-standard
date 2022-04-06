<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

use function preg_match;

class Deptrac extends Tool
{
    /** @var string */
    protected $name = 'deptrac';

    public function run(Context $context): bool
    {
        $outputFile = $this->createTempReportFile();

        if (
            $this->execute(self::vendorBinary('deptrac'), [
                '--formatter=xml',
                '--no-progress',
                '--no-interaction',
                $this->useNewOutputFormat()
                    ? "--output={$outputFile}"
                    : "--xml-dump={$outputFile}",
            ]) === 0
        ) {
            return true;
        }

        $entries = simplexml_load_string(file_get_contents($outputFile));

        if ($entries === false) {
            return false;
        }

        foreach ($entries->entry as $entry) {
            $layerA = (string) $entry->LayerA;
            $layerB = (string) $entry->LayerB;
            $classA = (string) $entry->ClassA;
            $classB = (string) $entry->ClassB;

            $occurrence = $entry->occurrence;

            $violation = new Violation();
            $violation->message = "{$classA} must not depend on {$classB}";
            $violation->source = "{$layerA} on {$layerB}";
            $violation->tool = $this->getName();
            $violation->line = (int) $occurrence['line'];

            $file = new File();
            $file->violations[] = $violation;

            $result = new Result();
            $result->files[(string) $occurrence['file']] = $file;

            $context->addResult($result);
        }

        return false;
    }

    protected function useNewOutputFormat(): bool
    {
        $output = [];
        $matches = [];

        $this->execute(self::vendorBinary('deptrac'), ['--version'], $output);

        preg_match('/(?<version>[\d.]+)/', implode(' ', $output), $matches);

        if (isset($matches['version'])) {
            return ((float) $matches['version']) >= 0.19;
        }

        return false;
    }
}
