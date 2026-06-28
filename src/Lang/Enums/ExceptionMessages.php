<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Lang
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const TRANSLATION_FILES_NOT_FOUND = 'Translation files not found.';

    public const MISCONFIGURED_DEFAULT_LANG = 'Misconfigured lang default config.';
}
