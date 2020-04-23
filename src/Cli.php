<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use Spaceemotion\PhpCodingStandard\Tools\Tool;

class Cli
{
    public const FLAG_CI = 'ci';

    public const FLAG_CONTINUE = 'continue';

    public const FLAG_FIX = 'fix';

    public const FLAG_HELP = 'help';

    private const HELP = [
        self::FLAG_CI => 'Changes the output format to checkstyle.xml for better CI integration',
        self::FLAG_FIX => 'Try to fix any linting errors (disables other tools)',
        self::FLAG_CONTINUE => 'Just run the next check if the previous one failed',
        self::FLAG_HELP => 'Displays this help message',
    ];

    private array $files;

    private array $flags;

    public function __construct(array $arguments)
    {
        $options = getopt('', array_keys(self::HELP));

        $this->flags = array_combine(
            array_keys(self::HELP),
            array_map(
                static fn ($key, $flag) => array_key_exists($flag, $options),
                self::HELP,
                array_keys(self::HELP),
            ),
        );

        $this->files = array_slice($arguments, count($options) + 1);
    }

    /**
     * Starts the whole application.
     *
     * @param Tool[] $tools A list of supported tools
     * @return int Exit code
     */
    public function start(array $tools): int
    {
        if ($this->hasFlag(self::FLAG_HELP)) {
            $this->showHelp();
            return 0;
        }

        $context = new Context();
        $context->config = new Config();
        $context->files = $this->getFiles() ?: $context->config->getSources();
        $context->isFixing = $this->hasFlag(self::FLAG_FIX);
        $context->runningInCi = $this->hasFlag(self::FLAG_CI);

        if (count($context->files) === 0) {
            echo 'No files specified.' . PHP_EOL;
            return 1;
        }

        $continue = $this->hasFlag(self::FLAG_CONTINUE) || $context->config->shouldContinue();

        foreach ($tools as $tool) {
            if (! $tool->run($context) && ! $continue) {
                echo PHP_EOL;
                return 1;
            }
        }

        return 0;
    }

    public function hasFlag(string $flag): bool
    {
        return array_key_exists($flag, $this->flags) && $this->flags[$flag] === true;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Shows an overview of all available flags and options.
     */
    private function showHelp(): void
    {
        echo 'Usage:' . PHP_EOL;
        echo '  phpcstd [options] <files or folders>' . PHP_EOL . PHP_EOL;

        echo 'Options:' . PHP_EOL;

        foreach (self::HELP as $flag => $message) {
            echo "  --{$flag}" . PHP_EOL . "    ${message}" . PHP_EOL . PHP_EOL;
        }
    }
}
