<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use RuntimeException;
use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

use function dirname;
use function file_exists;

use const DIRECTORY_SEPARATOR;

class Psalm extends Tool
{
    /** @var string */
    protected $name = 'psalm';

    public function run(Context $context): bool
    {
        $binary = self::vendorBinary($this->name);

        $config = $this->getConfigOption($context, $binary);

        if ($context->isFixing) {
            $this->execute($binary, array_merge(
                ['--alter', '--issues=all', '--no-progress'],
                $config,
                $context->files
            ));
        }

        $tmpFileJson = $this->createTempReportFile();
        $output = [];
        $exitCode = $this->execute($binary, array_merge(
            [
                '--monochrome',
                "--report=${tmpFileJson}",
                '--no-progress',
            ],
            $config,
            $context->files
        ), $output);

        $contents = file_get_contents($tmpFileJson);

        if ($contents === false) {
            throw new RuntimeException('Unable to read report file');
        }

        $json = self::parseJson($contents);

        if ($json === []) {
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

    /**
     * @return string[]
     *
     * @psalm-return array{0?: string}
     */
    protected function getConfigOption(Context $context, string $binary): array
    {
        // Detect correct config location and pass it on to psalm
        $configName = $context->config->getPart('psalm')['config'] ?? 'psalm.xml';
        $configName = $configName[0] !== '/'
            ? dirname($binary, 3) . DIRECTORY_SEPARATOR . $configName
            : $configName;

        return file_exists($configName) ? ["--config=${configName}"] : [];
    }
}
