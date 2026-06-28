<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Jwt\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Jwt
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const MISSING_PAYLOAD = 'JWT payload is missing.';
}
