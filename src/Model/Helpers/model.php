<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Database\Contracts\DbalInterface;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Model\DbModel;
use Quantum\Model\Model;

/**
 * Gets the model instance
 * @param class-string<T> $modelClass
 * @return T
 * @throws ModelException|BaseException|ReflectionException
 * @template T of Model
 */
function model(string $modelClass): Model
{
    return ModelFactory::get($modelClass);
}

/**
 * Creates anonymous dynamic model
 * @param array<string> $foreignKeys
 * @param array<string> $hidden
 */
function dynamicModel(
    string $table,
    string $modelName = '@anonymous',
    string $idColumn = 'id',
    array  $foreignKeys = [],
    array  $hidden = []
): DbModel {
    return ModelFactory::createDynamicModel(
        $table,
        $modelName,
        $idColumn,
        $foreignKeys,
        $hidden
    );
}

/**
 * Wraps the orm instance into model
 * @throws ModelException|BaseException
 */
function wrapToModel(?DbalInterface $ormInstance, string $modelClass): ?DbModel
{
    if ($ormInstance === null) {
        return null;
    }

    if (!class_exists($modelClass)) {
        throw ModelException::notFound('Model class', $modelClass);
    }

    $model = new $modelClass();

    if (!$model instanceof DbModel) {
        throw ModelException::notInstanceOf($modelClass, DbModel::class);
    }

    $model->setOrmInstance($ormInstance);
    $model->hydrateFromOrm($ormInstance->asArray());

    return $model;
}
