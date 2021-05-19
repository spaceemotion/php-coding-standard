<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use Spaceemotion\PhpCodingStandard\Commands\RunCommand;
use Spaceemotion\PhpCodingStandard\Tools\ComposerNormalize;
use Spaceemotion\PhpCodingStandard\Tools\Deptrac;
use Spaceemotion\PhpCodingStandard\Tools\EasyCodingStandard;
use Spaceemotion\PhpCodingStandard\Tools\Phan;
use Spaceemotion\PhpCodingStandard\Tools\PhpCodeSniffer;
use Spaceemotion\PhpCodingStandard\Tools\PhpMessDetector;
use Spaceemotion\PhpCodingStandard\Tools\PhpParallelLint\PhpParallelLint;
use Spaceemotion\PhpCodingStandard\Tools\Phpstan;
use Spaceemotion\PhpCodingStandard\Tools\Psalm;
use Spaceemotion\PhpCodingStandard\Tools\Rector;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/init.php';

$command = new RunCommand();
$command->addTool(new ComposerNormalize());
$command->addTool(new PhpParallelLint());
$command->addTool(new Deptrac());
$command->addTool(new Rector());
$command->addTool(new EasyCodingStandard());
$command->addTool(new PhpCodeSniffer());
$command->addTool(new PhpMessDetector());
$command->addTool(new Phpstan());
$command->addTool(new Psalm());
$command->addTool(new Phan());

$application = new Application('phpcstd');

$application->add($command);
$application->setDefaultCommand($command->getName());
$application->run();
