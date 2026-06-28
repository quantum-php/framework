<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Session\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Session
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const SESSION_NOT_STARTED = 'Can not start the session.';

    public const SESSION_NOT_DESTROYED = 'Can not destroy the session.';
}
