<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Exceptions;

use Quantum\Database\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class DatabaseException
 * @package Quantum\Database
 */
class DatabaseException extends BaseException
{
    public static function incorrectConfig(): self
    {
        return new self(
            ExceptionMessages::INCORRECT_CONFIG,
            E_ERROR
        );
    }

    public static function operatorNotSupported(string $operator): self
    {
        return new self(
            _message(ExceptionMessages::NOT_SUPPORTED_OPERATOR, [$operator]),
            E_WARNING
        );
    }

    public static function tableAlreadyExists(string $name): self
    {
        return new self(
            _message(ExceptionMessages::TABLE_ALREADY_EXISTS, $name),
            E_ERROR
        );
    }

    public static function tableDoesNotExists(string $name): self
    {
        return new self(
            _message(ExceptionMessages::TABLE_NOT_EXISTS, $name),
            E_ERROR
        );
    }
}
