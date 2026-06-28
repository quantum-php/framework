<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Hook\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Hook
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const DUPLICATE_HOOK_NAME = 'The Hook `{%1}` already registered.';

    public const UNREGISTERED_HOOK_NAME = 'The Hook `{%1}` was not registered.';
}
