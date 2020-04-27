<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;

class Phpstan extends Tool
{
    protected string $name = 'phpstan';

    public function run(Context $context): bool
    {
        return $this->execute($this->name, [
            'analyse',
            ...$context->files,
            $context->runningInCi
                ? '--no-ansi --error-format=checkstyle'
                : '--ansi',
        ]) === 0;
    }
}
