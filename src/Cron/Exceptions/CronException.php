<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cron\Exceptions;

use Quantum\Cron\Enums\ExceptionMessages;

/**
 * Class CronException
 * @package Quantum\Cron
 */
class CronException extends \Exception
{
    /**
     * Task not found exception
     */
    public static function taskNotFound(string $taskName): CronException
    {
        return new self(sprintf(ExceptionMessages::TASK_NOT_FOUND, $taskName));
    }

    /**
     * Invalid cron expression exception
     */
    public static function invalidExpression(string $expression): CronException
    {
        return new self(sprintf(ExceptionMessages::INVALID_EXPRESSION, $expression));
    }

    /**
     * Lock acquire failed exception
     */
    public static function lockAcquireFailed(string $taskName): CronException
    {
        return new self(sprintf(ExceptionMessages::LOCK_ACQUIRE_FAILED, $taskName));
    }

    /**
     * Task execution failed exception
     */
    public static function taskExecutionFailed(string $taskName, string $error): CronException
    {
        return new self(sprintf(ExceptionMessages::TASK_EXECUTION_FAILED, $taskName, $error));
    }

    /**
     * Invalid task file exception
     */
    public static function invalidTaskFile(string $file): CronException
    {
        return new self(sprintf(ExceptionMessages::INVALID_TASK_FILE, $file));
    }

    /**
     * Cron directory not found exception
     */
    public static function cronDirectoryNotFound(string $directory): CronException
    {
        return new self(sprintf(ExceptionMessages::CRON_DIRECTORY_NOT_FOUND, $directory));
    }

    /**
     * Lock directory not writable exception
     */
    public static function lockDirectoryNotWritable(string $directory): CronException
    {
        return new self(sprintf(ExceptionMessages::LOCK_DIRECTORY_NOT_WRITABLE, $directory));
    }
}
