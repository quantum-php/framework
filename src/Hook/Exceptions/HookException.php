<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Hook\Exceptions;

use Quantum\Hook\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class HookException
 * @package Quantum\Hook
 */
class HookException extends BaseException
{
    public static function hookDuplicateName(string $name): self
    {
        return new self(
            _message(ExceptionMessages::DUPLICATE_HOOK_NAME, [$name]),
            E_ERROR
        );
    }

    public static function unregisteredHookName(string $name): self
    {
        return new self(
            _message(ExceptionMessages::UNREGISTERED_HOOK_NAME, [$name]),
            E_WARNING
        );
    }
}
