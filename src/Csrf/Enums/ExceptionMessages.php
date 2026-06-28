<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Csrf\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Csrf
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const CSRF_TOKEN_MISSING = 'CSRF Token is missing';

    public const CSRF_TOKEN_MISMATCH = 'CSRF Token does not matched';
}
