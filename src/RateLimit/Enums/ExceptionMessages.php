<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\RateLimit\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\RateLimit
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const ADAPTER_NOT_SUPPORTED = 'Rate limit adapter `{%1}` is not supported.';
}
