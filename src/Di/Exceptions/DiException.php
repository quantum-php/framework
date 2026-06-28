<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Di\Exceptions;

use Quantum\Di\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class DiException
 * @package Quantum\Di
 */
class DiException extends BaseException
{
    public static function dependencyNotRegistered(string $name): DiException
    {
        return new self(
            _message(ExceptionMessages::DEPENDENCY_NOT_REGISTERED, [$name]),
            E_ERROR
        );
    }

    public static function dependencyAlreadyRegistered(string $name): DiException
    {
        return new self(
            _message(ExceptionMessages::DEPENDENCY_ALREADY_REGISTERED, $name),
            E_ERROR
        );
    }

    public static function dependencyNotInstantiable(string $name): DiException
    {
        return new self(
            _message(ExceptionMessages::DEPENDENCY_NOT_INSTANTIABLE, $name),
            E_ERROR
        );
    }

    public static function invalidAbstractDependency(string $name): DiException
    {
        return new self(
            _message(ExceptionMessages::INVALID_ABSTRACT_DEPENDENCY, $name),
            E_ERROR
        );
    }

    public static function circularDependency(string $chain): DiException
    {
        return new self(
            _message(ExceptionMessages::CIRCULAR_DEPENDENCY, [$chain]),
            E_ERROR
        );
    }

    public static function invalidCallable(?string $entry = null): DiException
    {
        return new self(
            _message(ExceptionMessages::INVALID_CALLABLE, [$entry ?? '']),
            E_ERROR
        );
    }
}
