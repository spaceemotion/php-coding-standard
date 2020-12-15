<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Spaceemotion\PhpCodingStandard\Cli;
use Spaceemotion\PhpCodingStandard\ExitException;

class TestCase extends BaseTestCase
{
    protected function createCliInstance(string ...$arguments): Cli
    {
        return new Cli(array_merge(['phpcstd'], $arguments));
    }

    protected function expectExit(string $message): void
    {
        $this->expectException(ExitException::class);
        $this->expectExceptionMessage($message);
    }
}
