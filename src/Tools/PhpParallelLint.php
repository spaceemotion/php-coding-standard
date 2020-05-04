<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use JakubOnderka\PhpParallelLint\Settings;
use Spaceemotion\PhpCodingStandard\Context;
use Throwable;

class PhpParallelLint extends Tool
{
    public const NAME = 'parallel-lint';

    protected $name = self::NAME;

    public function run(Context $context): bool
    {
        $manager = new PhpParallelLint\Manager($context);

        $settings = new Settings();
        $settings->addPaths($context->files);

        try {
            $result = $manager->run($settings);

            return ! $result->hasError();
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception->getTraceAsString());
            return false;
        }
    }
}
