<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

/**
 * Prints a dot ('.') for every advancement.
 */
class ProgressOutput
{
    /** @var int */
    protected $dotsPerLine;

    /** @var int */
    protected $dotsInLine;

    public function __construct(int $dotsPerLine = 64)
    {
        $this->dotsPerLine = $dotsPerLine;
        $this->dotsInLine = $dotsPerLine;
    }

    public function __destruct()
    {
        echo ' ';
    }

    public function advance(): void
    {
        if ($this->dotsInLine >= $this->dotsPerLine) {
            echo "\n   ";

            $this->dotsInLine = 0;
        }

        $this->dotsInLine++;

        echo '.';
    }
}
