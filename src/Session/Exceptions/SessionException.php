<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Session\Exceptions;

use Quantum\Session\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class SessionException
 * @package Quantum\Session
 */
class SessionException extends BaseException
{
    public static function sessionNotStarted(): self
    {
        return new self(
            ExceptionMessages::SESSION_NOT_STARTED,
            E_WARNING
        );
    }

    public static function sessionNotDestroyed(): self
    {
        return new self(
            ExceptionMessages::SESSION_NOT_DESTROYED,
            E_WARNING
        );
    }
}
