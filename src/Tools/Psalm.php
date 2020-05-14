<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use RuntimeException;
use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

class Psalm extends Tool
{
    protected $name = 'psalm';

    public function run(Context $context): bool
    {
        $binary = self::vendorBinary($this->name);

        if ($context->isFixing) {
            $this->execute($binary, array_merge(
                ['--alter', '--issues=all'],
                $context->files
            ));
        }

        $tmpFileJson = $this->createTempReportFile();
        $output = [];
        $exitCode = $this->execute($binary, array_merge(
            [
                '--debug',
                '--monochrome',
                "--report=${tmpFileJson}",
            ],
            $context->files
        ), $output, [$this, 'trackProgress']);

        $contents = file_get_contents($tmpFileJson);

        if ($contents === false) {
            throw new RuntimeException('Unable to read report file');
        }

        $json = self::parseJson($contents);

        if (count($json) === 0) {
            if ($exitCode === 0) {
                return true;
            }

            echo implode("\n", $output) . ' ';
            return false;
        }

        foreach ($json as $entry) {
            $violation = new Violation();
            $violation->message = $entry['message'];
            $violation->tool = $this->name;
            $violation->line = $entry['line_from'];
            $violation->source = "${entry['type']} (${entry['link']})";
            $violation->severity = $entry['severity'] === Violation::SEVERITY_ERROR
                ? Violation::SEVERITY_ERROR
                : Violation::SEVERITY_WARNING;

            $file = new File();
            $file->violations[] = $violation;

            $result = new Result();
            $result->files[$entry['file_path']] = $file;

            $context->addResult($result);
        }

        return $exitCode === 0;
    }

    protected function createTempReportFile(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), $this->name);

        if ($tmpFile === false) {
            throw new RuntimeException('Unable to create temporary report file');
        }

        $tmpFileJson = "${tmpFile}.json";

        if (! rename($tmpFile, $tmpFileJson)) {
            throw new RuntimeException('Unable to rename temporary report file');
        }

        return $tmpFileJson;
    }

    protected function trackProgress(string $line): bool
    {
        return stripos($line, 'Getting') === 0;
    }
}
