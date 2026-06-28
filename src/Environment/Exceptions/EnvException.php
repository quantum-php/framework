<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Environment\Exceptions;

use Quantum\Environment\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class EnvException
 * @package Quantum\Environment
 */
class EnvException extends BaseException
{
    public static function environmentImmutable(): self
    {
        return new self(
            ExceptionMessages::IMMUTABLE_ENVIRONMENT,
            E_ERROR
        );
    }

    public static function environmentNotLoaded(): self
    {
        return new self(
            ExceptionMessages::ENVIRONMENT_NOT_LOADED,
            E_ERROR
        );
    }
}
