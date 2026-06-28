<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Archive\Exceptions;

use Quantum\Archive\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class CacheException
 * @package Quantum\Archive
 */
class ArchiveException extends BaseException
{
    public static function cantOpen(string $name): self
    {
        return new self(
            _message(ExceptionMessages::CANT_OPEN, $name),
            E_WARNING
        );
    }

    public static function missingArchiveName(): self
    {
        return new self(
            ExceptionMessages::NAME_NOT_SET,
            E_WARNING
        );
    }
}
