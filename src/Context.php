<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

class Context
{
    public Config $config;

    public array $files;

    public bool $isFixing;

    public bool $runningInCi;
}
