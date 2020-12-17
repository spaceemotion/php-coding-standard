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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class ContextOutput implements Output
{
    /**
     * @var Context
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $context;

    /** @var ProgressBar */
    private $progress;

    /** @var OutputInterface */
    private $output;

    public function __construct(IWriter $writer)
    {
        // Do nothing
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
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
        $this->progress = new ProgressBar($this->output, $count);
        $this->progress->setFormat('  %message%: %current%/%max% [%bar%] %percent:3s%%');
        $this->progress->setMessage(PhpParallelLint::NAME);
        $this->progress->start();
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
        $this->progress->clear();

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
