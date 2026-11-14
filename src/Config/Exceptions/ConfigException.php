<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config\Exceptions;

use Quantum\Config\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class ConfigException
 * @package Quantum\Config
 */
class ConfigException extends BaseException
{
    public static function configCollision(string $name): self
    {
        return new self(
            _message(ExceptionMessages::CONFIG_COLLISION, [$name]),
            E_WARNING
        );
    }

    public static function invalidValue(string $key, string $value): self
    {
        return new self(
            _message(ExceptionMessages::INVALID_WHOLE_NUMBER, [$key, $value]),
            E_ERROR
        );
    }
}
