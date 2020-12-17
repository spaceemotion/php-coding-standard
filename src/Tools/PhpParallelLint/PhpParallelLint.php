<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools\PhpParallelLint;

use JakubOnderka\PhpParallelLint\Settings;
use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Tools\Tool;

class PhpParallelLint extends Tool
{
    public const NAME = 'parallel-lint';

    /** @var string */
    protected $name = self::NAME;

    public function run(Context $context): bool
    {
        $config = $context->config->getPart($this->name);

        $manager = new Manager($context, $this->output);

        $settings = new Settings();
        $settings->addPaths($context->files);
        $settings->parallelJobs = (int) ($config['processes'] ?? 24);

        $result = $manager->run($settings);

        return ! $result->hasError();
    }
}
