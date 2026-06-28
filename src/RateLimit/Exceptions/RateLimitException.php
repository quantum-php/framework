<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\RateLimit\Exceptions;

use Quantum\RateLimit\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class RateLimitException
 * @package Quantum\RateLimit
 */
class RateLimitException extends BaseException
{
    public static function adapterNotSupported(string $adapter): self
    {
        return new self(
            _message(ExceptionMessages::ADAPTER_NOT_SUPPORTED, [$adapter]),
            E_ERROR
        );
    }
}
