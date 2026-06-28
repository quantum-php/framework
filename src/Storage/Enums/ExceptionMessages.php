<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Storage
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const DIRECTORY_NOT_EXISTS = 'The directory {%1} does not exists.';

    public const DIRECTORY_NOT_WRITABLE = 'The directory {%1} is not writable.';

    public const FILE_ALREADY_EXISTS = 'The file {%1} already exists.';

    public const FILE_TYPE_NOT_ALLOWED = 'The file type `{%1}` is not allowed.';
}
