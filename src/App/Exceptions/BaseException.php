<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Exceptions;

use Quantum\App\Enums\ExceptionMessages;
use Exception;

/**
 * Class BaseException
 * @package Quantum\App
 */
abstract class BaseException extends Exception
{
    final public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
    }

    public static function methodNotSupported(string $methodName, string $className): BaseException
    {
        return new static(
            _message(ExceptionMessages::METHOD_NOT_SUPPORTED, [$methodName, $className]),
            E_WARNING
        );
    }

    public static function adapterNotSupported(string $name): BaseException
    {
        return new static(
            _message(ExceptionMessages::ADAPTER_NOT_SUPPORTED, [$name]),
            E_ERROR
        );
    }

    public static function driverNotSupported(string $name): BaseException
    {
        return new static(
            _message(ExceptionMessages::DRIVER_NOT_SUPPORTED, [$name]),
            E_ERROR
        );
    }

    public static function fileNotFound(string $name): BaseException
    {
        return new static(
            _message(ExceptionMessages::FILE_NOT_FOUND, [$name]),
            E_ERROR
        );
    }

    public static function notFound(string $subject, string $name): BaseException
    {
        return new static(
            _message(ExceptionMessages::NOT_FOUND, [$subject, $name]),
            E_ERROR
        );
    }

    public static function notInstanceOf(string $instance, string $name): BaseException
    {
        return new static(
            _message(ExceptionMessages::NOT_INSTANCE_OF, [$instance, $name]),
            E_ERROR
        );
    }

    public static function cantConnect(string $name): BaseException
    {
        return new static(
            _message(ExceptionMessages::CANT_CONNECT, [$name]),
            E_ERROR
        );
    }

    public static function missingConfig(string $name): BaseException
    {
        return new static(
            _message(ExceptionMessages::MISSING_CONFIG, $name),
            E_ERROR
        );
    }

    public static function requestMethodNotAvailable(string $name): BaseException
    {
        return new static(
            _message(ExceptionMessages::UNAVAILABLE_REQUEST_METHOD, [$name]),
            E_WARNING
        );
    }
}
