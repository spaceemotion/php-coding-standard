<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use RuntimeException;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class Config
{
    public const IGNORE_SOURCES = 'ignoreSources';

    /** @var OutputInterface */
    protected $output;

    /** @var mixed[] */
    private $config = [];

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;

        foreach ($this->readConfigs() as $config) {
            $this->config = self::mergeConfig($this->config, $config);
        }
    }

    /**
     * Returns a list of file paths to lint/fix.
     *
     * @return string[]
     */
    public function getSources(): array
    {
        return $this->config['source'] ?? [];
    }

    public function shouldContinue(): bool
    {
        return (bool) ($this->config['continue'] ?? false);
    }

    public function shouldAutoFix(): bool
    {
        return (bool) ($this->config['autofix'] ?? false);
    }

    public function isEnabled(string $toolName): bool
    {
        return (bool) ($this->config[$toolName]['enabled'] ?? false);
    }

    /**
     * @return mixed[]
     */
    public function getPart(string $toolName): array
    {
        $contents = $this->config[$toolName] ?? [];

        if (! is_array($contents)) {
            return [];
        }

        return $contents;
    }

    /**
     * @return mixed[]
     */
    protected function readConfig(string $path): array
    {
        if (! is_file($path)) {
            $path = preg_replace('/\.ini$/i', '.dist.ini', $path);

            if ($path === null || ! is_file($path)) {
                return [];
            }
        }

        $this->output->writeln("Including config: {$path}", Output::VERBOSITY_VERBOSE);

        $config = parse_ini_file($path, true);

        if ($config === false) {
            throw new RuntimeException(
                'Unable to parse config. Please make sure that it\'s a valid ini formatted file.'
            );
        }

        return $config;
    }

    /**
     * @param mixed[] $base
     * @param mixed[] $config
     * @return mixed[]
     */
    protected static function mergeConfig(array $base, array $config): array
    {
        foreach ($config as $key => $value) {
            // Check if value is an associative array
            if (is_array($value) && array_keys($value) !== range(0, count($value) - 1)) {
                $value = self::mergeConfig($base[$key] ?? [], $value);
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * @return array[]
     *
     * @psalm-return array<string, array>
     */
    protected function readConfigs(): array
    {
        $workingDirectory = getcwd();

        if ($workingDirectory === false) {
            throw new RuntimeException('Unable to get working directory.');
        }

        $configs = [];

        $paths = array_unique(array_filter([
            $workingDirectory . '/.phpcstd.ini',
            dirname(__DIR__) . '/.phpcstd.ini',
        ]));

        while (($path = array_shift($paths)) !== null) {
            $config = $this->readConfig($path);

            foreach (array_reverse((array) ($config['include'] ?? [])) as $include) {
                $includePath = realpath(dirname($path) . '/' . $include);

                // Either the file does not exist
                if ($includePath === false) {
                    $this->output->writeln(
                        "Could not find config {$include}, skipping",
                        Output::VERBOSITY_NORMAL
                    );
                    continue;
                }

                // or we've already loaded its contents
                if (array_key_exists($includePath, $configs)) {
                    $this->output->writeln(
                        "Cyclic dependency found at ${path} for ${includePath}",
                        Output::VERBOSITY_NORMAL
                    );
                    continue;
                }

                array_unshift($paths, $includePath);
            }

            unset($config['include']);

            $configs[$path] = $config;
        }

        return array_reverse($configs);
    }
}
