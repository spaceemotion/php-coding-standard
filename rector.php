<?php

/** @noinspection TransitiveDependenciesUsageInspection */

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\MoveOutMethodCallInsideIfConditionRector;
use Rector\Config\RectorConfig;
use Rector\Core\Configuration\Option;
use Rector\Performance\Rector\FuncCall\PreslashSimpleFunctionRector;
use Rector\Set\ValueObject\SetList;
use Rector\SOLID\Rector\Class_\ChangeReadOnlyVariableWithDefaultValueToConstantRector;
use Rector\SOLID\Rector\Class_\FinalizeClassesWithoutChildrenRector;

return static function (RectorConfig $config): void {
    $config->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/bin/phpcstd',
    ]);

    $config->skip([
        MoveOutMethodCallInsideIfConditionRector::class,
        PreslashSimpleFunctionRector::class,
        ChangeReadOnlyVariableWithDefaultValueToConstantRector::class,
        FinalizeClassesWithoutChildrenRector::class,
    ]);

    // Define what rule sets will be applied
    $config->sets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        //SetList::CODE_QUALITY_STRICT,
        SetList::UNWRAP_COMPAT,
        SetList::TYPE_DECLARATION,
        //SetList::SOLID,
        // SetList::PERFORMANCE,
        SetList::EARLY_RETURN,
        //SetList::DEAD_CLASSES,

        //SetList::PHPSTAN,

        //SetList::PHPUNIT_CODE_QUALITY,
        //SetList::PHPUNIT_SPECIFIC_METHOD,
        //SetList::PHPUNIT_EXCEPTION,
        //SetList::PHPUNIT_YIELD_DATA_PROVIDER,

        SetList::PHP_71,
    ]);

    $config->importNames();
};
