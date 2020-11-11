<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard\Tools\PhpParallelLint;

use JakubOnderka\PhpParallelLint\ErrorFormatter;
use JakubOnderka\PhpParallelLint\IWriter;
use JakubOnderka\PhpParallelLint\Output;
use JakubOnderka\PhpParallelLint\SyntaxError;
use Spaceemotion\PhpCodingStandard\Context;
use Spaceemotion\PhpCodingStandard\Formatter\File;
use Spaceemotion\PhpCodingStandard\Formatter\Result;
use Spaceemotion\PhpCodingStandard\Formatter\Violation;
use Spaceemotion\PhpCodingStandard\ProgressOutput;
use Spaceemotion\PhpCodingStandard\Tools\PhpParallelLint;

class ContextOutput implements Output
{
    /**
     * @var Context
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $context;

    /** @var ProgressOutput */
    private $progress;

    public function __construct(IWriter $writer)
    {
        $this->progress = new ProgressOutput();
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    /**
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function ok(): void
    {
        $this->progress->advance();
    }

    public function skip(): void
    {
        $this->ok();
    }

    public function error(): void
    {
        $this->ok();
    }

    public function fail(): void
    {
        $this->ok();
    }

    /**
     * @psalm-suppress MissingParamType
     */
    public function setTotalFileCount($count): void
    {
    }

    /**
     * @psalm-suppress MissingParamType
     */
    public function writeHeader($phpVersion, $parallelJobs, $hhvmVersion = null): void
    {
    }

    /**
     * @psalm-suppress MissingParamType
     */
    public function writeResult(
        \JakubOnderka\PhpParallelLint\Result $result,
        ErrorFormatter $errorFormatter,
        $ignoreFails
    ): void {
        foreach ($result->getErrors() as $error) {
            if (! ($error instanceof SyntaxError)) {
                continue;
            }

            $violation = new Violation();
            $violation->line = (int) $error->getLine();
            $violation->tool = PhpParallelLint::NAME;
            $violation->message = preg_replace(
                '~ in (.*?) on line \d+$~',
                '',
                $error->getNormalizedMessage()
            );

            $file = new File();
            $file->violations[] = $violation;

            $result = new Result();
            $result->files[$error->getFilePath()] = $file;

            $this->context->addResult($result);
        }
    }
}
