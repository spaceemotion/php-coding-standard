<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Closure;
use RuntimeException;
use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;
use Spaceemotion\PhpCodingStandard\ProgressTracker;

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
                ['--alter', '--issues=all'],
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
            ],
            $config,
            $context->files
        ), $output, $context->fast ? null : new ProgressTracker(
            Closure::fromCallable([$this, 'trackProgress']),
            ['--debug']
        ));

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
