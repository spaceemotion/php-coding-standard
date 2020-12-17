<?php

declare(strict_types=1);

namespace Spaceemotion\PhpCodingStandard;

use Spaceemotion\PhpCodingStandard\Commands\RunCommand;
use Symfony\Component\Console\Application;

require_once 'init.php';

$command = new RunCommand();
$command->addTool(new Tools\ComposerNormalize());
$command->addTool(new Tools\PhpParallelLint\PhpParallelLint());
$command->addTool(new Tools\Rector());
$command->addTool(new Tools\EasyCodingStandard());
$command->addTool(new Tools\PhpCodeSniffer());
$command->addTool(new Tools\PhpMessDetector());
$command->addTool(new Tools\Phpstan());
$command->addTool(new Tools\Psalm());
$command->addTool(new Tools\Phan());

$application = new Application('phpcstd');

$application->add($command);
$application->setDefaultCommand($command->getName());
$application->run();
