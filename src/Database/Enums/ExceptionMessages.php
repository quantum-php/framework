<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Database
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const INCORRECT_CONFIG = 'The structure of config is not correct';

    public const NOT_SUPPORTED_OPERATOR = 'The operator `{%1}` is not supported';

    public const TABLE_ALREADY_EXISTS = 'The table `{%1}` is already exists';

    public const TABLE_NOT_EXISTS = 'The table `{%1}` does not exists';
}
