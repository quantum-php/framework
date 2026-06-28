<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Paginator
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const MISSING_REQUIRED_PARAMS = 'Missing required parameter `{%1}` missing for adapter {%2}';
}
