<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger\Enums;

/**
 * Class LoggerType
 * @package Quantum\Logger
 * @codeCoverageIgnore
 */
final class LoggerType
{
    public const SINGLE = 'single';

    public const DAILY = 'daily';

    public const MESSAGE = 'message';

    private function __construct()
    {
    }
}
