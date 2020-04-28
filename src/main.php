<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

require_once 'init.php';

exit((new Cli($argv))->start([
    new Tools\PhpParallelLint(),
    new Tools\EasyCodingStandard(),
    new Tools\PhpMessDetector(),
    new Tools\Phpstan(),
]) ? 1 : 0);
