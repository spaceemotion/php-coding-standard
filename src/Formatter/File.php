<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Formatter;

class File
{
    /** @var Violation[] */
    public array $violations = [];

    public function add(self $file): self
    {
        $this->violations = array_merge($this->violations, $file->violations);

        return $this;
    }
}
