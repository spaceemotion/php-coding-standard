<?php

declare(strict_types=1);

namespace Tests;

use Spaceemotion\PhpCodingStandard\Cli;

class CliTest extends TestCase
{
    public function test_it_starts_up_without_arguments(): void
    {
        $cli = $this->createCliInstance();

        self::assertTrue($cli->start([]));

        self::assertNotEmpty($this->getActualOutputForAssertion());
    }

    public function test_it_runs_tools(): void
    {
        $this->expectOutputRegex('/PhpUnit/');

        $cli = $this->createCliInstance();

        $tool = new TestTool();

        self::assertTrue($cli->start([$tool]));
    }

    public function test_it_parses_flags_and_parameters(): void
    {
        $cli = $this->createCliInstance(
            '--fast',
            '--only=foo,bar,baz'
        );

        self::assertTrue($cli->hasFlag(Cli::FLAG_FAST));

        self::assertSame(
            ['foo', 'bar', 'baz'],
            $cli->getParameter(Cli::PARAMETER_ONLY)
        );

        self::assertNotEmpty($this->getActualOutputForAssertion());
    }

    public function test_it_shows_options_when_help_flag_is_passed(): void
    {
        $this->expectExit('--' . Cli::FLAG_FAST);

        $this->createCliInstance('--help');
    }
}
