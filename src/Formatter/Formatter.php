<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

use Symfony\Component\Console\Style\SymfonyStyle;

interface Formatter
{
    public function format(Result $result, SymfonyStyle $style): void;
}
