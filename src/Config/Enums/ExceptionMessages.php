<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Config
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const CONFIG_COLLISION = 'Config key `{%1}` is already in use';
}
