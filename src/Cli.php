<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use RuntimeException;
use Spaceemotion\PhpCodingStandard\Formatter\ConsoleFormatter;
use Spaceemotion\PhpCodingStandard\Formatter\GithubActionFormatter;
use Spaceemotion\PhpCodingStandard\Tools\Tool;

use function array_filter;
use function array_map;
use function substr;

use const PHP_EOL;

class Cli
{
    public const FLAG_CI = 'ci';

    public const FLAG_FAST = 'fast';

    public const FLAG_CONTINUE = 'continue';

    public const FLAG_FIX = 'fix';

    public const FLAG_HELP = 'help';

    public const FLAG_ANSI = 'ansi';

    public const FLAG_HIDE_SOURCE = 'hide-source';

    public const FLAG_LINT_STAGED = 'lint-staged';

    public const FLAG_NO_FAIL = 'no-fail';

    public const PARAMETER_DISABLE = 'disable';

    public const PARAMETER_ONLY = 'only';

    private const OPTIONS = [
        self::PARAMETER_DISABLE => 'Disables the list of tools during the run (comma-separated list)',
        self::PARAMETER_ONLY => 'Only executes the list of tools during the run (comma-separated list)',
        self::FLAG_ANSI => 'Forces the output to be colorized',
        self::FLAG_CI => 'Changes the output format to checkstyle.xml for better CI integration',
        self::FLAG_FAST => 'Disables all progress tracking so tools can use their cache (where applicable)',
        self::FLAG_FIX => 'Try to fix any linting errors (disables other tools)',
        self::FLAG_CONTINUE => 'Just run the next check if the previous one failed',
        self::FLAG_HIDE_SOURCE => 'Hides the "source" lines from console output',
        self::FLAG_NO_FAIL => 'Only returns with exit code 0, regardless of any errors/warnings',
        self::FLAG_LINT_STAGED => 'Uses "git diff" to determine staged files to lint',
        self::FLAG_HELP => 'Displays this help message',
    ];

    /** @var string[] */
    private $files;

    /** @var string[] */
    private $flags;

    /** @var string[][] */
    private $parameters;

    /** @var Config */
    private $config;

    /**
     * @param string[] $arguments
     *
     * @throws ExitException
     */
    public function __construct(array $arguments)
    {
        [$this->flags, $this->parameters, $this->files] = $this->parseFlags(array_slice($arguments, 1));

        if ($this->hasFlag(self::FLAG_HELP)) {
            throw new ExitException($this->showHelp());
        }

        $this->lintStaged();

        $this->parseFilesFromInput();

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
        $context->fast = $this->hasFlag(self::FLAG_FAST);

        $success = $this->executeContext($tools, $context);

        $formatter = $context->runningInCi
            ? new GithubActionFormatter($this)
            : new ConsoleFormatter($this);

        $formatter->format($context->result);

        return $this->hasFlag(self::FLAG_NO_FAIL) ? true : $success;
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, $this->flags, true);
    }

    /**
     * @return string[]
     */
    public function getParameter(string $parameter): array
    {
        return $this->parameters[$parameter] ?? [];
    }

    public static function isOnWindows(): bool
    {
        if (defined('PHP_OS_FAMILY')) {
            return PHP_OS_FAMILY === 'Windows';
        }

        return stripos(PHP_OS, 'WIN') === 0;
    }

    /**
     * Calls 'git diff' to determine changed files.
     * Also exists if no files have been changed.
     *
     * @throws ExitException
     */
    private function lintStaged(): void
    {
        if (! $this->hasFlag(self::FLAG_LINT_STAGED)) {
            return;
        }

        $output = [];

        exec('git diff --name-status --cached', $output);

        $this->files = array_filter(array_map(static function (string $line): string {
            // Only count added or modified files
            return $line[0] === 'A' || $line[0] === 'M' ? ltrim(substr($line, 1)) : '';
        }, $output));

        if ($this->files === []) {
            throw new ExitException('No files staged. Skipping.');
        }
    }

    /**
     * Shows an overview of all available flags and options.
     *
     * @return string
     */
    private function showHelp(): string
    {
        $help = 'Usage:' . PHP_EOL;
        $help .= '  phpcstd [options] <files or folders>' . PHP_EOL . PHP_EOL;

        $help .= 'Options:' . PHP_EOL;

        foreach (self::OPTIONS as $flag => $message) {
            $help .= "  --{$flag}" . PHP_EOL . "    ${message}" . PHP_EOL . PHP_EOL;
        }

        return $help;
    }

    /**
     * @param Tool[] $tools
     */
    private function executeContext(array $tools, Context $context): bool
    {
        $disabled = $this->getParameter(self::PARAMETER_DISABLE);
        $only = $this->getParameter(self::PARAMETER_ONLY);

        $continue = $this->hasFlag(self::FLAG_CONTINUE) || $this->config->shouldContinue();
        $success = true;

        foreach ($tools as $tool) {
            $name = $tool->getName();

            if (
                in_array($name, $disabled, true)
                || ($only !== [] && ! in_array($name, $only, true))
            ) {
                continue;
            }

            echo "-> {$name}: ";

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
     * @return (mixed|string|string[])[][]
     *
     * @psalm-return array{0: list<string>, 1: array<string, list<string>>, 2: list<mixed>}
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
                $parameters[$flag] = $this->parseList($value);
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

        // Fix "sh: turning off NDELAY mode" error on exit
        register_shutdown_function(function (): void {
            stream_set_blocking(STDIN, true);
        });

        // Read each file path per line from the input
        while (($file = fgets(STDIN)) !== false) {
            $this->files[] = trim($file);
        }
    }

    /**
     * @return string[]
     *
     * @psalm-return list<string>
     */
    private function parseList(string $list): array
    {
        if ($list === '') {
            return [];
        }

        return array_map('trim', explode(',', $list));
    }
}
