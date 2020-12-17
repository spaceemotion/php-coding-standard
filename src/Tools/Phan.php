<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

use function file_get_contents;

class Phan extends Tool
{
    /** @var string */
    protected $name = 'phan';

    public function run(Context $context): bool
    {
        $outputFile = $this->createTempReportFile();

        if (
            $this->execute(self::vendorBinary('phan'), array_merge(
                [
                    '--output-mode=json',
                    '--output=' . $outputFile,
                    '--no-color',
                ],
                extension_loaded('ast') ? [] : ['--allow-polyfill-parser'],
                $context->isFixing ? ['--automatic-fix'] : []
            )) === 0
        ) {
            return true;
        }

        $json = self::parseJson(file_get_contents($outputFile));

        if ($json === []) {
            return false;
        }

        foreach ($json as $entry) {
            $violation = new Violation();
            $violation->message = $entry['description'];
            $violation->tool = $this->name;
            $violation->line = $entry['location']['lines']['begin'];
            $violation->source = "${entry['check_name']} (${entry['type_id']})";

            $file = new File();
            $file->violations[] = $violation;

            $result = new Result();
            $result->files[$entry['location']['path']] = $file;

            $context->addResult($result);
        }

        return false;
    }
}
