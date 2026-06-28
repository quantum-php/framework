<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cron\Contracts;

/**
 * Interface CronTaskInterface
 * @package Quantum\Cron
 */
interface CronTaskInterface
{
    /**
     * Get the cron expression
     */
    public function getExpression(): string;

    /**
     * Get the task name
     */
    public function getName(): string;

    /**
     * Check if the task should run at the current time
     */
    public function shouldRun(): bool;

    /**
     * Execute the task
     */
    public function handle(): void;
}
