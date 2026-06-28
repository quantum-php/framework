<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Csrf\Exceptions;

use Quantum\Csrf\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class CsrfException
 * @package Quantum\Csrf
 */
class CsrfException extends BaseException
{
    public static function tokenNotFound(): self
    {
        return new self(
            ExceptionMessages::CSRF_TOKEN_MISSING,
            E_WARNING
        );
    }

    public static function tokenNotMatched(): self
    {
        return new self(
            ExceptionMessages::CSRF_TOKEN_MISMATCH,
            E_WARNING
        );
    }
}
