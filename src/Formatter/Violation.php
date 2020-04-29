<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

class Violation
{
    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_ERROR = 'error';

    public const SEVERITY_INFO = 'info';

    /** @var int */
    public $line = 0;

    /** @var string */
    public $severity = self::SEVERITY_ERROR;

    /** @var string */
    public $message = '';

    /** @var string */
    public $source = '';

    /** @var string */
    public $tool = '';
}
