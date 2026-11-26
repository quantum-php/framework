<?php

declare(strict_types=1);

use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        SetList::TYPE_DECLARATION,
    ])
    ->withRules([
        SafeDeclareStrictTypesRector::class,
    ])
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        SafeDeclareStrictTypesRector::class => [
            __DIR__ . '/src/*/Helpers/*',
        ],
    ]);
