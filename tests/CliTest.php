<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Spaceemotion\PhpCodingStandard\Commands\RunCommand;
use Symfony\Component\Console\Tester\CommandTester;

class CliTest extends TestCase
{
    public function test_it_starts_up_without_arguments(): void
    {
        $tester = new CommandTester(new RunCommand());
        $tester->execute([]);

        self::assertNotEmpty($tester->getDisplay());
    }

    public function test_it_runs_tools(): void
    {
        self::markTestSkipped('Unable to provide custom tool list for now');
    }
}
