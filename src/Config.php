<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use RuntimeException;

class Config
{
    private array $config;

    public function __construct(string $path = PHPCSTD_ROOT . '.phpcstd')
    {
        if (! is_file($path)) {
            $path .= '.dist';
        }

        if (! is_file($path)) {
            $this->config = [];
            return;
        }

        $config = parse_ini_file($path, true);

        if ($config === false) {
            throw new RuntimeException(
                'Unable to parse config. Please make sure that it\'s a valid ini formatted file.'
            );
        }

        $this->config = $config;
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
}
