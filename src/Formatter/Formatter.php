<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

interface Formatter
{
    public function format(Result $result, \Symfony\Component\Console\Style\SymfonyStyle $style): void;
}
