<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger;

/**
 * Class LoggerConfig
 * @package Quantum\Logger
 */
class LoggerConfig
{
    /**
     * Log levels mapped to integer values.
     */
    public const LOG_LEVELS = [
        'debug' => 100,
        'info' => 200,
        'notice' => 250,
        'warning' => 300,
        'error' => 400,
        'critical' => 500,
        'alert' => 550,
        'emergency' => 600,
    ];

    /**
     * Default log level.
     */
    public const DEFAULT_LOG_LEVEL = 'error';

    private static string $logLevel = self::DEFAULT_LOG_LEVEL;

    /**
     * Set the application's log level.
     */
    public static function setAppLogLevel(string $level): void
    {
        if (isset(self::LOG_LEVELS[$level])) {
            self::$logLevel = $level;
        }
    }

    /**
     * Get the integer value of the application's log level.
     */
    public static function getAppLogLevel(): int
    {
        return self::LOG_LEVELS[self::$logLevel] ?? self::LOG_LEVELS[self::DEFAULT_LOG_LEVEL];
    }

    /**
     * Get the integer log level for a given error type.
     */
    public static function getLogLevel(string $errorType): int
    {
        return self::LOG_LEVELS[$errorType] ?? self::LOG_LEVELS[self::DEFAULT_LOG_LEVEL];
    }
}
