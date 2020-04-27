<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;

class PhpMessDetector extends Tool
{
    protected string $name = 'phpmd';

    public function run(Context $context): bool
    {
        return $this->execute($this->name, [
            implode(',', $context->files),
            $context->runningInCi ? 'xml' : 'ansi',
            'phpmd.xml',
        ]) === 0;
    }
}
