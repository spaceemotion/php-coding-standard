<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use Spaceemotion\PhpCodingStandard\Formatter\Result;

class Context
{
    public Config $config;

    public array $files = [];

    public bool $isFixing = false;

    public bool $runningInCi = false;

    public array $toolsExecuted = [];

    public Result $result;

    public function __construct()
    {
        $this->result = new Result();
    }

    public function addResult(Result $result): void
    {
        $this->result->add($result);
    }
}
