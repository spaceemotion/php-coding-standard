<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use Spaceemotion\PhpCodingStandard\Formatter\Result;

class Context
{
    /** @var Config */
    public $config;

    /** @var string[] */
    public $files = [];

    /** @var bool */
    public $isFixing = false;

    /** @var bool */
    public $runningInCi = false;

    /** @var string[] */
    public $toolsExecuted = [];

    /** @var Result */
    public $result;

    public function __construct()
    {
        $this->result = new Result();
    }

    public function addResult(Result $result): void
    {
        $this->result->add($result);
    }
}
