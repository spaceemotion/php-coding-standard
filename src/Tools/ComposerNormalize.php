<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\DiffViolation;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

use function implode;
use function preg_match;
use function trim;

use const PHP_EOL;

class ComposerNormalize extends Tool
{
    protected const COMPOSER_FILE = 'composer.json';

    /** @var string */
    protected $name = 'composer-normalize';

    public function shouldRun(Context $context): bool
    {
        // TODO does not check against file names, only full paths
        if (! in_array(self::COMPOSER_FILE, $context->files, true)) {
            return false;
        }

        return parent::shouldRun($context);
    }

    public function run(Context $context): bool
    {
        $config = $context->config->getPart($this->name);

        $binary = $config['binary'] ?? 'composer';
        $filename = PHPCSTD_ROOT . self::COMPOSER_FILE;

        $output = [];

        if (
            $this->execute($binary, [
                'normalize',
                $filename,
                '--diff',
                '--no-update-lock',
                $context->isFixing ? '' : '--dry-run',
            ], $output) === 0
        ) {
            return true;
        }

        $file = new File();
        $text = trim(implode(PHP_EOL, $output));

        $matches = [];

        if (preg_match('/^-{10,} begin diff -{10,}(.+)^-{10,} end diff -{10,}/ms', $text, $matches) === 1) {
            $file->violations = DiffViolation::make($this, $matches[1], static function (): string {
                return 'File is not normalized';
            });
        }

        if ($file->violations === []) {
            $violation = new Violation();
            $violation->message = $text;
            $violation->tool = $this->getName();

            $file->violations[] = $violation;
        }

        $result = new Result();
        $result->files[$filename] = $file;

        $context->addResult($result);

        return false;
    }
}
