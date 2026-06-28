<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Model\Exceptions;

use Quantum\Model\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class ModelException
 * @package Quantum\Model
 */
class ModelException extends BaseException
{
    public static function inappropriateProperty(string $name): self
    {
        return new self(
            _message(ExceptionMessages::INAPPROPRIATE_MODEL_PROPERTY, $name),
            E_WARNING
        );
    }

    public static function wrongRelation(string $modelName, string $tableName): self
    {
        return new self(
            _message(ExceptionMessages::WRONG_RELATION, [$modelName, $tableName]),
            E_ERROR
        );
    }

    public static function relationTypeMissing(string $modelName, string $relatedModelName): self
    {
        return new self(
            _message(ExceptionMessages::RELATION_TYPE_MISSING, [$modelName, $relatedModelName]),
            E_ERROR
        );
    }

    public static function missingRelationKeys(string $modelName, string $relatedModelName): self
    {
        return new self(
            _message(ExceptionMessages::MISSING_RELATION_KEYS, [$modelName, $relatedModelName]),
            E_ERROR
        );
    }

    public static function missingForeignKeyValue(string $modelName, string $foreignKey): self
    {
        return new self(
            _message(ExceptionMessages::MISSING_FOREIGN_KEY, [$foreignKey, $modelName]),
            E_ERROR
        );
    }

    public static function unsupportedRelationType(string $relationType): self
    {
        return new self(
            _message(ExceptionMessages::UNSUPPORTED_RELATION, [$relationType]),
            E_ERROR
        );
    }

    public static function ormIsNotSet(): self
    {
        return new self(
            ExceptionMessages::ORM_IS_NOT_SET,
            E_ERROR
        );
    }
}
