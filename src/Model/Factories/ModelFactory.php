<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Model\Factories;

use Quantum\Database\Contracts\DbalInterface;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Model\DbModel;
use Quantum\Model\Model;
use ReflectionException;

/**
 * Class ModelFactory
 * @package Quantum\Model
 */
class ModelFactory
{
    /**
     * Gets the Model
     * @template T of Model
     * @param class-string<T> $modelClass
     * @return T
     * @throws ModelException|BaseException|ReflectionException
     */
    public static function get(string $modelClass): Model
    {
        if (!class_exists($modelClass)) {
            throw ModelException::notFound('Model', $modelClass);
        }

        $model = new $modelClass();

        if (!$model instanceof Model) {
            throw ModelException::notInstanceOf($modelClass, Model::class);
        }

        if ($model instanceof DbModel) {
            $ormInstance = self::createOrmInstance(
                $model->table,
                $modelClass,
                $model->idColumn,
                $model->relations(),
                $model->hidden ?? []
            );

            $model->setOrmInstance($ormInstance);
        }

        return $model;
    }

    /**
     * Creates anonymous dynamic model
     * @param array<string> $foreignKeys
     * @param array<string> $hidden
     * @throws DiException|BaseException|ReflectionException
     */
    public static function createDynamicModel(
        string $table,
        string $modelName = '@anonymous',
        string $idColumn = 'id',
        array  $foreignKeys = [],
        array  $hidden = []
    ): DbModel {
        $model = new class () extends DbModel {};

        $ormInstance = self::createOrmInstance(
            $table,
            $modelName,
            $idColumn,
            $foreignKeys,
            $hidden
        );

        $model->setOrmInstance($ormInstance);

        return $model;
    }

    /**
     * @param array<string> $foreignKeys
     * @param array<string> $hidden
     * @throws DiException|BaseException|ReflectionException
     */
    protected static function createOrmInstance(
        string $table,
        string $modelName,
        string $idColumn,
        array  $foreignKeys = [],
        array  $hidden = []
    ): DbalInterface {
        $ormClass = db()->getOrmClass();

        $instance = new $ormClass(
            $table,
            $modelName,
            $idColumn,
            $foreignKeys,
            $hidden
        );

        if (!$instance instanceof DbalInterface) {
            throw ModelException::notInstanceOf($ormClass, DbalInterface::class);
        }

        return $instance;
    }
}
