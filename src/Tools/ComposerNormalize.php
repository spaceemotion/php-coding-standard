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

        $output = [];

        if (
            $this->execute($binary, [
                'normalize',
                $filename,
                '--no-update-lock',
                $context->isFixing ? '' : '--dry-run',
            ], $output) === 0
        ) {
            return true;
        }

        $file = new File();

        if (strpos($output[0], 'is not normalized') !== false) {
            $violation = new Violation();
            $violation->message = 'File is not normalized';
            $violation->source = trim(implode(PHP_EOL, array_slice($output, 4, count($output) - 6)));
            $violation->tool = $this->name;

            $file->violations[] = $violation;
        }

        if (! isset($violation)) {
            $source = '';

            if (strpos($output[0], 'not valid according to schema') !== false) {
                $source = $output[count($output) - 1];
                $output = array_slice($output, 1, count($output) - 2);
            }

            foreach ($output as $message) {
                $violation = new Violation();
                $violation->message = $message;
                $violation->source = $source;
                $violation->tool = $this->name;

                $file->violations[] = $violation;
            }
        }

        $result = new Result();
        $result->files[$filename] = $file;

        $context->addResult($result);

        return false;
    }
}
