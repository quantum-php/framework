<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Exceptions;

use Quantum\App\Enums\ExceptionMessages;

/**
 * Class AppException
 * @package Quantum\App
 */
class AppException extends BaseException
{
    public static function missingAppKey(): self
    {
        return new self(
            ExceptionMessages::APP_KEY_MISSING,
            E_ERROR
        );
    }
}
