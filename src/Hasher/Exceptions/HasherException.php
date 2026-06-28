<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Hasher\Exceptions;

use Quantum\Hasher\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class HasherException
 * @package Quantum\Hasher
 */
class HasherException extends BaseException
{
    public static function algorithmNotSupported(string $algorithm): self
    {
        return new self(
            _message(ExceptionMessages::ALGORITHM_NOT_SUPPORTED, $algorithm),
            E_WARNING
        );
    }

    public static function invalidBcryptCost(): self
    {
        return new self(
            ExceptionMessages::INVALID_BCRYPT_COST,
            E_WARNING
        );
    }
}
