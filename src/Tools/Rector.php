<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\DiffViolation;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;

use function array_map;
use function basename;
use function file_get_contents;
use function implode;
use function preg_replace;
use function str_replace;
use function strtolower;

class Rector extends Tool
{
    /** @var string */
    protected $name = 'rector';

    public function run(Context $context): bool
    {
        $outputFile = $this->createTempReportFile();
        $binary = self::vendorBinary('rector');

        if (
            $this->execute($binary, array_merge([
                'process',
                '--output-format=json',
                '--output-file=' . $outputFile,
                '--no-progress-bar',
                $context->isFixing ? '' : '--dry-run',
            ], $context->files)) === 0
        ) {
            return true;
        }

        $json = self::parseJson(file_get_contents($outputFile));

        $result = new Result();

        foreach ($json['file_diffs'] as $diff) {
            $file = new File();
            $file->violations = DiffViolation::make($this, $diff['diff'], function (int $idx) use ($diff): string {
                return $idx > 0
                    ? '(contd.)'
                    : "Code issues:\n- " . implode(
                        "\n- ",
                        self::prettifyRectors($diff['applied_rectors'])
                    );
            });

            $result->files[$diff['file']] = $file;
        }

        $context->addResult($result);

        return false;
    }

    /**
     * @param string[] $applied_rectors
     * @return string[]
     *
     * @psalm-return array<array-key, string>
     */
    private static function prettifyRectors(array $applied_rectors): array
    {
        return array_map(static function (string $rector): string {
            $className = basename(str_replace(['\\'], '/', $rector));
            $withoutSuffix = preg_replace('/Rector$/', '', $className);

            $name = strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $withoutSuffix));

            return $name . " <gray>(${rector})</gray>";
        }, $applied_rectors);
    }
}
