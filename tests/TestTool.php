<?php

declare(strict_types=1);

namespace Tests;

use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Tools\Tool;

class TestTool extends Tool
{
    public function __construct(string $name = 'PhpUnit')
    {
        $this->name = $name;
    }

    /**
     * @return true
     */
    public function run(Context $context): bool
    {
        return true;
    }
}
