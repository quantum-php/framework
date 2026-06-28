<?php

declare(strict_types=1);

use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictStringParamConcatRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_80,
        SetList::TYPE_DECLARATION,
    ])
    ->withRules([
        DeclareStrictTypesRector::class,
    ])
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        StrictStringParamConcatRector::class => [
            __DIR__ . '/src/Database/Adapters/Idiorm/Statements/Criteria.php',
        ],
    ]);
