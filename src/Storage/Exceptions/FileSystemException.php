<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Exceptions;

use Quantum\Storage\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class FileSystemException
 * @package Quantum\Storage
 */
class FileSystemException extends BaseException
{
    public static function directoryNotExists(string $name): self
    {
        return new self(
            _message(ExceptionMessages::DIRECTORY_NOT_EXISTS, [$name]),
            E_WARNING
        );
    }

    public static function directoryNotWritable(string $name): self
    {
        return new self(
            _message(ExceptionMessages::DIRECTORY_NOT_WRITABLE, [$name]),
            E_WARNING
        );
    }

    public static function fileAlreadyExists(string $path): self
    {
        return new self(
            _message(ExceptionMessages::FILE_ALREADY_EXISTS, [$path]),
            E_WARNING
        );
    }
}
