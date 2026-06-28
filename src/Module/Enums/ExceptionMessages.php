<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Module\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Module
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const MODULE_ROUTES_NOT_FOUND = 'Routes not found for module `{%1}`';

    public const MODULE_CONFIG_NOT_FOUND = 'Config not found for a module';

    public const MODULE_CREATION_INCOMPLETE = 'Module creation incomplete: missing files.';

    public const MISSING_MODULE_TEMPLATE = 'Template `{%1}` does not exist';

    public const MISSING_MODULE_DIRECTORY = 'Module directory does not exist, skipping config update.';

    public const MODULE_ALREADY_EXISTS = 'A module or prefix named `{%1}` already exists';

    public const DIRECTORY_LISTING_FAILED = 'Failed to list directory `{%1}`';
}
