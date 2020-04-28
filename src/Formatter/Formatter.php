<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

interface Formatter
{
    public function format(Result $result): void;
}
