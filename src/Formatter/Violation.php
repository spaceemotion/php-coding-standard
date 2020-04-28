<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

class Violation
{
    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_ERROR = 'error';

    public int $line = 0;

    public int $column = 0;

    public string $severity = self::SEVERITY_ERROR;

    public string $message = '';

    public string $source = '';

    public string $tool = '';
}
