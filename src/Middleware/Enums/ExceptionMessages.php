<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Middleware\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Middleware
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const MIDDLEWARE_NOT_FOUND = 'Middleware class `{%1}` not found.';
}
