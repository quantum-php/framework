<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger\Exceptions;

use Quantum\Logger\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class LoggerException
 * @package Quantum\Logger
 */
class LoggerException extends BaseException
{
    public static function logPathIsNotDirectory(string $name): self
    {
        return new self(
            _message(ExceptionMessages::LOG_PATH_NOT_DIRECTORY, [$name]),
            E_ERROR
        );
    }

    public static function logPathIsNotFile(string $name): self
    {
        return new self(
            _message(ExceptionMessages::LOG_PATH_NOT_FILE, [$name]),
            E_ERROR
        );
    }
}
