<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Exceptions;

use Psr\SimpleCache\InvalidArgumentException;
use Quantum\Cache\Enums\ExceptionMessages;

/**
 * Class InvalidCacheKeyException
 * @package Quantum\Cache
 */
class InvalidCacheKeyException extends CacheException implements InvalidArgumentException
{
    public static function keyNotString(string $type): self
    {
        return new self(
            _message(ExceptionMessages::KEY_NOT_STRING, [$type]),
            E_WARNING
        );
    }
}
