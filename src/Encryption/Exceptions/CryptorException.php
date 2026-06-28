<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Encryption\Exceptions;

use Quantum\Encryption\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class CryptorException
 * @package Quantum\Encryption
 */
class CryptorException extends BaseException
{
    public static function publicKeyNotProvided(): self
    {
        return new self(
            ExceptionMessages::PUBLIC_KEY_MISSING,
            E_WARNING
        );
    }

    public static function privateKeyNotProvided(): self
    {
        return new self(
            ExceptionMessages::PRIVATE_KEY_MISSING,
            E_WARNING
        );
    }

    public static function invalidCipher(): self
    {
        return new self(
            ExceptionMessages::INVALID_CIPHER,
            E_WARNING
        );
    }
}
