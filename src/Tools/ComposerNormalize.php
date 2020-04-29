<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;

class ComposerNormalize extends Tool
{
    protected const COMPOSER_FILE = 'composer.json';

    protected $name = 'composer-normalize';

    public function shouldRun(Context $context): bool
    {
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

        if ($this->execute($binary, [
            'normalize',
            $filename,
            '--no-update-lock',
            $context->isFixing ? '' : '--dry-run',
        ]) === 0) {
            return true;
        }

        $violation = new Violation();
        $violation->message = 'composer.json is not normalized';
        $violation->tool = $this->name;

        $file = new File();
        $file->violations[] = $violation;

        $result = new Result();
        $result->files[$filename] = $file;

        $context->addResult($result);

        return false;
    }
}
