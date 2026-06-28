<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Archive\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Archive
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const CANT_OPEN = 'The archive `{%1}` can not be opened';

    public const NAME_NOT_SET = 'Archive name is not set';
}
