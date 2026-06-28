<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cron\Enums;

/**
 * Enum ExceptionMessages
 * @package Quantum\Cron
 * @codeCoverageIgnore
 */
final class ExceptionMessages
{
    public const TASK_NOT_FOUND = 'Cron task "%s" not found';
    public const INVALID_EXPRESSION = 'Invalid cron expression: %s';
    public const LOCK_ACQUIRE_FAILED = 'Failed to acquire lock for task "%s"';
    public const TASK_EXECUTION_FAILED = 'Task "%s" execution failed: %s';
    public const INVALID_TASK_FILE = 'Invalid task file "%s": must return array or CronTask instance';
    public const CRON_DIRECTORY_NOT_FOUND = 'Cron directory not found: %s';
    public const LOCK_DIRECTORY_NOT_WRITABLE = 'Lock directory is not writable: %s';

    private function __construct()
    {
    }
}
