<?php

/** @noinspection TransitiveDependenciesUsageInspection */

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/bin/phpcstd',
    ]);

    // Define what rule sets will be applied
    $parameters->set(Option::SETS, [
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::PHP_71,
    ]);

    $parameters->set(Option::AUTO_IMPORT_NAMES, true);

    // Run Rector only on changed files
    $parameters->set(Option::ENABLE_CACHE, true);
};
