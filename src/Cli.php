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

    private const OPTIONS = [
        self::FLAG_CI => 'Changes the output format to checkstyle.xml for better CI integration',
        self::FLAG_FIX => 'Try to fix any linting errors (disables other tools)',
        self::FLAG_CONTINUE => 'Just run the next check if the previous one failed',
        self::FLAG_HELP => 'Displays this help message',
    ];

    private array $files;

    private array $flags;

    private Config $config;

    public function __construct(array $arguments)
    {
        $options = getopt('', array_keys(self::OPTIONS));

        $this->flags = array_combine(
            array_keys(self::OPTIONS),
            array_map(
                static fn ($key, $flag) => array_key_exists($flag, $options),
                self::OPTIONS,
                array_keys(self::OPTIONS),
            ),
        );

        $this->files = array_slice($arguments, count($options) + 1);
    }

    /**
     * Starts the whole application.
     *
     * @param Tool[] $tools A list of supported tools
     * @return bool Success state
     */
    public function start(array $tools): bool
    {
        if ($this->hasFlag(self::FLAG_HELP)) {
            $this->showHelp();
            return true;
        }

        $this->config = new Config();

        if (count($this->files) === 0) {
            $this->files = $this->config->getSources();
        }

        if (count($this->files) === 0) {
            echo 'No files specified.' . PHP_EOL;
            return false;
        }

        $context = new Context();
        $context->config = $this->config;
        $context->files = $this->files;
        $context->isFixing = $this->hasFlag(self::FLAG_FIX);
        $context->runningInCi = $this->hasFlag(self::FLAG_CI);

        return $this->executeContext($tools, $context);
    }

    private function hasFlag(string $flag): bool
    {
        return array_key_exists($flag, $this->flags) && $this->flags[$flag] === true;
    }

    /**
     * Shows an overview of all available flags and options.
     */
    private function showHelp(): void
    {
        echo 'Usage:' . PHP_EOL;
        echo '  phpcstd [options] <files or folders>' . PHP_EOL . PHP_EOL;

        echo 'Options:' . PHP_EOL;

        foreach (self::OPTIONS as $flag => $message) {
            echo "  --{$flag}" . PHP_EOL . "    ${message}" . PHP_EOL . PHP_EOL;
        }
    }

    /**
     * @param Tool[] $tools
     */
    private function executeContext(array $tools, Context $context): bool
    {
        $continue = $this->hasFlag(self::FLAG_CONTINUE) || $this->config->shouldContinue();

        foreach ($tools as $tool) {
            if (! $tool->shouldRun($context)) {
                continue;
            }

            if (! $tool->run($context) && ! $continue) {
                return false;
            }

            $context->toolsExecuted[] = get_class($tool);
        }

        return true;
    }
}
