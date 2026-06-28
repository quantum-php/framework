<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\HttpClient
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const REQUEST_NOT_CREATED = 'Request is not created.';
}
