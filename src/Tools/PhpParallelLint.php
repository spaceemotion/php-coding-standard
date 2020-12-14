<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use JakubOnderka\PhpParallelLint\Settings;
use Spaceemotion\PhpCodingStandard\Context;
use Throwable;

class PhpParallelLint extends Tool
{
    public const NAME = 'parallel-lint';

    /** @var string */
    protected $name = self::NAME;

    public function run(Context $context): bool
    {
        $config = $context->config->getPart($this->name);

        $manager = new PhpParallelLint\Manager($context);

        $settings = new Settings();
        $settings->addPaths($context->files);
        $settings->parallelJobs = (int) ($config['processes'] ?? 24);

        try {
            $result = $manager->run($settings);

            return ! $result->hasError();
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception->getTraceAsString());
            return false;
        }
    }
}
