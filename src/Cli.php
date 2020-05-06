<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use RuntimeException;
use Spaceemotion\PhpCodingStandard\Formatter\ConsoleFormatter;
use Spaceemotion\PhpCodingStandard\Tools\Tool;

use function array_filter;
use function array_map;

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

    /** @var string[] */
    private $files;

    /** @var array<string, bool>|bool[] */
    private $flags;

    /** @var Config */
    private $config;

    /**
     * @param string[] $arguments
     */
    public function __construct(array $arguments)
    {
        $options = getopt('', array_keys(self::OPTIONS));

        $this->flags = $this->parseFlags($options);

        $this->files = array_slice($arguments, count($options) + 1);

        $this->config = new Config();
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

        if (count($this->files) === 0) {
            $this->files = $this->config->getSources();
        }

        $this->files = array_filter(
            array_filter(array_map('trim', $this->files)),
            static function (string $path): bool {
                if (is_file($path) || is_dir($path)) {
                    return true;
                }

                echo "Unable to locate source: ${path}\n";
                return false;
            }
        );

        if (count($this->files) === 0) {
            echo 'No (valid) files specified.' . PHP_EOL;
            return false;
        }

        $context = new Context($this->config);
        $context->files = $this->files;
        $context->isFixing = $this->hasFlag(self::FLAG_FIX) || $this->config->shouldAutoFix();
        $context->runningInCi = $this->hasFlag(self::FLAG_CI);

        $success = $this->executeContext($tools, $context);

        (new ConsoleFormatter())->format($context->result);

        return $success;
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
        $success = true;

        foreach ($tools as $tool) {
            echo "-> {$tool->getName()}: ";

            if (! $tool->shouldRun($context)) {
                echo 'SKIP' . PHP_EOL;
                continue;
            }

            $context->toolsExecuted[] = get_class($tool);

            if ($tool->run($context)) {
                echo 'OK' . PHP_EOL;
                continue;
            }

            echo 'FAIL' . PHP_EOL;

            if (! $continue) {
                return false;
            }

            $success = false;
        }

        return $success;
    }

    /**
     * @param mixed[] $options
     *
     * @return bool[]
     *
     * @psalm-return array<string, bool>
     */
    private function parseFlags(array $options): array
    {
        $flags = array_combine(
            array_keys(self::OPTIONS),
            array_map(
                static function ($key, $flag) use ($options): bool {
                    return array_key_exists($flag, $options);
                },
                self::OPTIONS,
                array_keys(self::OPTIONS)
            )
        );

        if ($flags === false) {
            throw new RuntimeException('Unable to parse flags');
        }

        return $flags;
    }
}
