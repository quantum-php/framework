<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Hasher\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Hasher
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const ALGORITHM_NOT_SUPPORTED = 'The algorithm {%1} not supported.';

    public const INVALID_BCRYPT_COST = 'Provided bcrypt cost is invalid.';
}
