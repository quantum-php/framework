<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Migration\Exceptions;

use Quantum\Migration\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * MigrationException class
 *
 * @package Quantum\Migration
 * @category Exceptions
 */
class MigrationException extends BaseException
{
    public static function wrongDirection(): self
    {
        return new self(
            ExceptionMessages::WRONG_MIGRATION_DIRECTION,
            E_ERROR
        );
    }

    public static function unsupportedAction(string $action): self
    {
        return new self(
            _message(ExceptionMessages::NOT_SUPPORTED_ACTION, [$action]),
            E_ERROR
        );
    }

    public static function nothingToMigrate(): self
    {
        return new self(
            ExceptionMessages::NOTHING_TO_MIGRATE,
            E_NOTICE
        );
    }

    public static function invalidMigrationClass(string $className): self
    {
        return new self(
            _message(ExceptionMessages::INVALID_MIGRATION_CLASS, [$className]),
            E_ERROR
        );
    }
}
