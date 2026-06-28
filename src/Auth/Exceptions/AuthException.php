<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth\Exceptions;

use Quantum\Auth\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class AuthException
 * @package Quantum\Auth
 */
class AuthException extends BaseException
{
    public static function incorrectCredentials(): self
    {
        return new self(
            ExceptionMessages::INCORRECT_CREDENTIALS,
            E_ERROR
        );
    }

    public static function inactiveAccount(): self
    {
        return new self(
            ExceptionMessages::INACTIVE_ACCOUNT,
            E_ERROR
        );
    }

    public static function incorrectVerificationCode(): self
    {
        return new self(
            ExceptionMessages::INCORRECT_VERIFICATION_CODE,
            E_ERROR
        );
    }

    public static function verificationCodeExpired(): self
    {
        return new self(
            ExceptionMessages::VERIFICATION_CODE_EXPIRED,
            E_ERROR
        );
    }

    public static function incorrectUserSchema(): self
    {
        return new self(
            ExceptionMessages::INCORRECT_USER_SCHEMA,
            E_ERROR
        );
    }
}
