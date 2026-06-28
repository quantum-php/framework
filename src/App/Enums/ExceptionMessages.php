<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Enums;

/**
 * Class ExceptionMessages
 * @package Quantum\App
 */
class ExceptionMessages
{
    public const APP_KEY_MISSING = 'APP KEY is missing.';

    public const EXECUTION_TERMINATED = 'Execution was terminated.';

    public const METHOD_NOT_SUPPORTED = 'The method `{%1}` is not supported for `{%2}`.';

    public const ADAPTER_NOT_SUPPORTED = 'The adapter `{%1}` is not supported.';

    public const DRIVER_NOT_SUPPORTED = 'The driver `{%1}` is not supported.';

    public const FILE_NOT_FOUND = 'The file `{%1}` not found.';

    public const NOT_FOUND = '{%1} `{%2}` not found.';

    public const NOT_INSTANCE_OF = 'The `{%1}` is not instance of `{%2}`.';

    public const CANT_CONNECT = 'Can not connect to `{%1}`.';

    public const MISSING_CONFIG = 'Could not load config `{%1}` properly.';

    public const UNAVAILABLE_REQUEST_METHOD = 'Provided request method `{%1}` is not available.';
}
