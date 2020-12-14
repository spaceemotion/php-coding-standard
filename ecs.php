<?php

/** @noinspection UnusedFunctionResultInspection */

/** @noinspection TransitiveDependenciesUsageInspection */

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(ArraySyntaxFixer::class)
        ->call('configure', [[
            'syntax' => 'short',
        ]]);

    $services->set(PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer::class)
        ->call('configure', [[
            'const' => 'single',
            'property' => 'single',
            'method' => 'multi',
        ]]);

    $services->set(PhpCsFixer\Fixer\Import\OrderedImportsFixer::class)
        ->call('configure', [[
            'sort_algorithm' => 'alpha',
            'imports_order' => ['const', 'class', 'function'],
        ]]);

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::LINE_ENDING, "\n");
    $parameters->set(Option::PATHS, [
        __DIR__ . '/bin',
        __DIR__ . '/src',
    ]);

    $parameters->set(Option::SETS, [
        SetList::COMMON,
        SetList::CLEAN_CODE,
        SetList::DEAD_CODE,
        SetList::PSR_12,
        SetList::PHP_70,
        SetList::PHP_71,
    ]);
};
