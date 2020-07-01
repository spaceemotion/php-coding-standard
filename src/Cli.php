<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use RuntimeException;
use Spaceemotion\PhpCodingStandard\Formatter\ConsoleFormatter;
use Spaceemotion\PhpCodingStandard\Formatter\GithubActionFormatter;
use Spaceemotion\PhpCodingStandard\Tools\Tool;

use function array_filter;
use function array_map;

class Cli
{
    public const FLAG_CI = 'ci';

    public const FLAG_CONTINUE = 'continue';

    public const FLAG_FIX = 'fix';

    public const FLAG_HELP = 'help';

    public const FLAG_ANSI = 'ansi';

    public const FLAG_HIDE_SOURCE = 'hide-source';

    public const PARAMETER_DISABLE = 'disable';

    private const OPTIONS = [
        self::PARAMETER_DISABLE => 'Disables the list of tools during the run (comma-separated list)',
        self::FLAG_ANSI => 'Forces the output to be colorized',
        self::FLAG_CI => 'Changes the output format to checkstyle.xml for better CI integration',
        self::FLAG_FIX => 'Try to fix any linting errors (disables other tools)',
        self::FLAG_CONTINUE => 'Just run the next check if the previous one failed',
        self::FLAG_HIDE_SOURCE => 'Hides the "source" lines from console output',
        self::FLAG_HELP => 'Displays this help message',
    ];

    /** @var string[] */
    private $files;

    /** @var string[] */
    private $flags;

    /** @var string[] */
    private $parameters;

    /** @var Config */
    private $config;

    /**
     * @param string[] $arguments
     */
    public function __construct(array $arguments)
    {
        [$this->flags, $this->parameters, $this->files] = $this->parseFlags(array_slice($arguments, 1));

        $this->config = new Config();

        $this->parseFilesFromInput();
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

        $formatter = $context->runningInCi
            ? new GithubActionFormatter($this)
            : new ConsoleFormatter($this);

        $formatter->format($context->result);

        return $success;
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, $this->flags, true);
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
        $disabled = explode(',', $this->parameters[self::PARAMETER_DISABLE] ?? '');

        $continue = $this->hasFlag(self::FLAG_CONTINUE) || $this->config->shouldContinue();
        $success = true;

        foreach ($tools as $tool) {
            $name = $tool->getName();

            echo "-> {$name}: ";

            if (in_array($name, $disabled, true) || ! $tool->shouldRun($context)) {
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
     * @return string[][]
     */
    private function parseFlags(array $options): array
    {
        $flags = [];
        $parameters = [];
        $files = [];

        foreach ($options as $option) {
            if (strpos($option, '--') !== 0) {
                $files[] = $option;
                continue;
            }

            $split = explode('=', substr($option, 2), 2);
            $flag = $split[0];
            $value = $split[1] ?? '';

            if (! array_key_exists($flag, self::OPTIONS)) {
                throw new RuntimeException("Unknown flag/parameter: '{$flag}'");
            }

            if ($value !== '') {
                $parameters[$flag] = $value;
                continue;
            }

            $flags[] = $flag;
        }

        return [$flags, $parameters, $files];
    }

    private function parseFilesFromInput(): void
    {
        // Windows does not support nonblocking input streams:
        // https://bugs.php.net/bug.php?id=34972
        if (self::isOnWindows()) {
            return;
        }

        // Don't block execution if we don't have any piped input
        stream_set_blocking(STDIN, false);

        // Read each file path per line from the input
        while (($file = fgets(STDIN)) !== false) {
            $this->files[] = trim($file);
        }
    }

    public static function isOnWindows(): bool
    {
        if (defined('PHP_OS_FAMILY')) {
            return PHP_OS_FAMILY === 'Windows';
        }

        return stripos(PHP_OS, 'WIN') === 0;
    }
}
