<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use RuntimeException;

class Config
{
    /** @var mixed[] */
    private $config;

    public function __construct()
    {
        $this->config = [];

        foreach (self::readConfigs() as $config) {
            $this->config = self::mergeConfig($this->config, $config);
        }

        echo PHP_EOL;
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
    protected static function readConfig(string $path): array
    {
        if (! is_file($path)) {
            $path = preg_replace('/\.ini$/i', '.dist.ini', $path);

            if ($path === null || ! is_file($path)) {
                return [];
            }
        }

        echo "Including config: {$path}\n";

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
     * @return mixed[]
     */
    protected static function readConfigs(): array
    {
        $configs = [];

        $paths = array_unique(array_filter([
            dirname(__DIR__) . '/.phpcstd.ini',
            PHPCSTD_ROOT . '.phpcstd.ini',
        ]));

        while (($path = array_shift($paths)) !== null) {
            $config = self::readConfig($path);

            foreach (array_reverse((array) ($config['include'] ?? [])) as $include) {
                $includePath = realpath(dirname($path) . '/' . $include);

                // Either the file does not exist
                if ($includePath === false) {
                    echo "Could not find config ${includePath}, skipping\n";
                    continue;
                }

                // or we've already loaded its contents
                if (array_key_exists($includePath, $configs)) {
                    echo "Cyclic dependency found at ${path} for ${includePath}\n";
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
