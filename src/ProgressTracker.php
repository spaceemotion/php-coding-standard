<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

class ProgressTracker
{
    /** @var callable */
    public $callback;

    /** @var string[] */
    public $arguments;

    /**
     * @param string[] $arguments
     */
    public function __construct(callable $callback, array $arguments)
    {
        $this->callback = $callback;
        $this->arguments = $arguments;
    }
}
