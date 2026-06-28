<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Exceptions;

use Quantum\App\Enums\ExceptionMessages as ExceptionMessagesAlias;
use Quantum\Storage\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class FileUploadException
 * @package Quantum\Storage
 */
class FileUploadException extends BaseException
{
    public static function fileTypeNotAllowed(string $name): self
    {
        return new self(
            _message(ExceptionMessages::FILE_TYPE_NOT_ALLOWED, [$name]),
            E_WARNING
        );
    }

    public static function incorrectMimeTypesConfig(string $name): self
    {
        return new self(
            _message(ExceptionMessagesAlias::MISSING_CONFIG, $name),
            E_ERROR
        );
    }
}
