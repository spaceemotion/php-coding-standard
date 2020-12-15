<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

class File
{
    public const GLOBAL = '- Global errors -';

    /** @var Violation[] */
    public $violations = [];

    /**
     * @return static
     */
    public function add(self $file): self
    {
        $this->violations = array_merge($this->violations, $file->violations);

        return $this;
    }
}
