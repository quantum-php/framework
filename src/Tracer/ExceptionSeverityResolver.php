<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Tracer;

use ReflectionException;
use Psr\Log\LogLevel;
use ErrorException;
use ParseError;
use Throwable;

final class ExceptionSeverityResolver
{
    /**
     * @var array<int, string>
     */
    private const ERROR_TYPES = [
        E_ERROR => 'error',
        E_WARNING => 'warning',
        E_PARSE => 'error',
        E_NOTICE => 'notice',
        E_CORE_ERROR => 'error',
        E_CORE_WARNING => 'warning',
        E_COMPILE_ERROR => 'error',
        E_COMPILE_WARNING => 'warning',
        E_USER_ERROR => 'error',
        E_USER_WARNING => 'warning',
        E_USER_NOTICE => 'notice',
        E_STRICT => 'notice',
        E_RECOVERABLE_ERROR => 'error',
    ];

    public function resolve(Throwable $throwable): string
    {
        if ($throwable instanceof ErrorException) {
            return self::ERROR_TYPES[$throwable->getSeverity()] ?? LogLevel::ERROR;
        }

        if ($throwable instanceof ParseError) {
            return LogLevel::CRITICAL;
        }

        if ($throwable instanceof ReflectionException) {
            return LogLevel::WARNING;
        }

        return LogLevel::ERROR;
    }
}
