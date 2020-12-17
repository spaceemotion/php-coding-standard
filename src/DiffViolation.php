<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use Spaceemotion\PhpCodingStandard\Formatter\Violation;
use Spaceemotion\PhpCodingStandard\Tools\Tool;

use function preg_match_all;
use function preg_replace;
use function trim;

use const PREG_SET_ORDER;

class DiffViolation
{
    /**
     * @return Violation[]
     *
     * @psalm-return list<Violation>
     */
    public static function make(Tool $tool, string $raw, callable $messageCallback): array
    {
        $violations = [];
        $matches = [];

        // 0 = all
        // 1 = from line number
        // 2 = from length
        // 3 = to line number
        // 4 = to length
        // 5 = diff excerpt
        preg_match_all(
            '/^@@ -(\d+),(\d+) \+(\d+),(\d+) @@(.+?)(?=@@|\Z)/ms',
            $raw,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $idx => $match) {
            $message = $messageCallback($idx);

            $fromLineNumber = (int) $match[1];

            $highlighted = preg_replace(
                ['/^-.*/m', '/^\+.*/m'],
                ['<fg=red>$0</>', '<fg=green>$0</>'],
                trim($match[5], "\n\r")
            );

            $violation = new Violation();
            $violation->message = $message;
            $violation->tool = $tool->getName();
            $violation->source = "...\n" . $highlighted . "\n...";

            // offset from the diff
            $violation->line = $fromLineNumber + 3;

            $violations[] = $violation;
        }

        return $violations;
    }
}
