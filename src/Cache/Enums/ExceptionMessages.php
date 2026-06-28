<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cache\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Cache
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const ARGUMENT_NOT_ITERABLE = 'The argument {%1} is not iterable';
}
