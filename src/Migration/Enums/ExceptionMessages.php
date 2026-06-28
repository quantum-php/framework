<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Migration\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Migration
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const WRONG_MIGRATION_DIRECTION = 'Migration direction can only be [up] or [down]';

    public const NOT_SUPPORTED_ACTION = 'The action `{%1}`, is not supported';

    public const NOTHING_TO_MIGRATE = 'Nothing to migrate';

    public const INVALID_MIGRATION_CLASS = 'Migration class `{%1}` must extend Migration';
}
