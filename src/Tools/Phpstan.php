<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;

class Phpstan extends Tool
{
    public function run(Context $context): bool
    {
        return $this->execute('phpstan', [
            'analyse',
            ...$context->files,
            $context->runningInCi
                ? '--no-ansi --error-format=checkstyle'
                : '--ansi',
        ]) === 0;
    }
}
