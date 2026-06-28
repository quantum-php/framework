<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Asset\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Asset
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const POSITION_IN_USE = 'Position `{%1}` for asset `{%2}` is in use';

    public const NAME_IN_USE = 'The name {%1} is in use';
}
