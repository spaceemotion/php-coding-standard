<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools\PhpParallelLint;

use JakubOnderka\PhpParallelLint\Manager as BaseManager;
use JakubOnderka\PhpParallelLint\NullWriter;
use JakubOnderka\PhpParallelLint\Output;
use JakubOnderka\PhpParallelLint\Settings;
use Spaceemotion\PhpCodingStandard\Context;
use Symfony\Component\Console\Output\OutputInterface;

class Manager extends BaseManager
{
    public function __construct(Context $context, OutputInterface $output)
    {
        $this->output = new ContextOutput(new NullWriter());
        $this->output->setContext($context);
        $this->output->setOutput($output);
    }

    protected function getDefaultOutput(Settings $settings): Output
    {
        return $this->output;
    }
}
