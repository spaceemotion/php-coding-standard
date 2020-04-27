<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools;

use Spaceemotion\PhpCodingStandard\Context;

class EasyCodingStandard extends Tool
{
    protected bool $canFix = true;

    protected string $name = 'ecs';

    public function run(Context $context): bool
    {
        return $this->execute($this->name, [
            'check',
            ...$context->files,
            $context->isFixing ? '--fix' : '',
        ]) === 0;
    }
}
