<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Logger
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const LOG_PATH_NOT_DIRECTORY = 'Log path is not point to a directory.';

    public const LOG_PATH_NOT_FILE = 'Log path is not point to a file.';
}
