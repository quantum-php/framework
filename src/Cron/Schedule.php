<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cron;

use Quantum\Cron\Exceptions\CronException;

/**
 * Class Schedule
 * Fluent API for creating cron schedules
 * @package Quantum\Cron
 */
class Schedule
{
    /**
     * Task name
     */
    private string $name;

    /**
     * Cron expression
     */
    private ?string $expression = null;

    /**
     * Task callback
     * @var callable|null
     */
    private $callback;

    /**
     * Schedule constructor
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Run the task every minute
     */
    public function everyMinute(): self
    {
        $this->expression = '* * * * *';
        return $this;
    }

    /**
     * Run the task every five minutes
     */
    public function everyFiveMinutes(): self
    {
        $this->expression = '*/5 * * * *';
        return $this;
    }

    /**
     * Run the task every ten minutes
     */
    public function everyTenMinutes(): self
    {
        $this->expression = '*/10 * * * *';
        return $this;
    }

    /**
     * Run the task every fifteen minutes
     */
    public function everyFifteenMinutes(): self
    {
        $this->expression = '*/15 * * * *';
        return $this;
    }

    /**
     * Run the task every thirty minutes
     */
    public function everyThirtyMinutes(): self
    {
        $this->expression = '*/30 * * * *';
        return $this;
    }

    /**
     * Run the task hourly
     */
    public function hourly(): self
    {
        $this->expression = '0 * * * *';
        return $this;
    }

    /**
     * Run the task hourly at a specific minute
     */
    public function hourlyAt(int $minute): self
    {
        $this->expression = "{$minute} * * * *";
        return $this;
    }

    /**
     * Run the task every two hours
     */
    public function everyTwoHours(): self
    {
        $this->expression = '0 */2 * * *';
        return $this;
    }

    /**
     * Run the task every three hours
     */
    public function everyThreeHours(): self
    {
        $this->expression = '0 */3 * * *';
        return $this;
    }

    /**
     * Run the task every four hours
     */
    public function everyFourHours(): self
    {
        $this->expression = '0 */4 * * *';
        return $this;
    }

    /**
     * Run the task every six hours
     */
    public function everySixHours(): self
    {
        $this->expression = '0 */6 * * *';
        return $this;
    }

    /**
     * Run the task daily
     */
    public function daily(): self
    {
        $this->expression = '0 0 * * *';
        return $this;
    }

    /**
     * Run the task daily at a specific time
     * @param string $time Format: "HH:MM"
     */
    public function dailyAt(string $time): self
    {
        [$hour, $minute] = explode(':', $time);
        $hour = (int) $hour;
        $minute = (int) $minute;
        $this->expression = "{$minute} {$hour} * * *";
        return $this;
    }

    /**
     * Run the task twice daily
     */
    public function twiceDaily(int $firstHour = 1, int $secondHour = 13): self
    {
        $this->expression = "0 {$firstHour},{$secondHour} * * *";
        return $this;
    }

    /**
     * Run the task weekly
     */
    public function weekly(): self
    {
        $this->expression = '0 0 * * 0';
        return $this;
    }

    /**
     * Run the task weekly on a specific day and time
     * @param int $dayOfWeek 0-6 (Sunday = 0)
     * @param string $time Format: "HH:MM"
     */
    public function weeklyOn(int $dayOfWeek, string $time = '0:00'): self
    {
        [$hour, $minute] = explode(':', $time);
        $hour = (int) $hour;
        $minute = (int) $minute;
        $this->expression = "{$minute} {$hour} * * {$dayOfWeek}";
        return $this;
    }

    /**
     * Run the task monthly
     */
    public function monthly(): self
    {
        $this->expression = '0 0 1 * *';
        return $this;
    }

    /**
     * Run the task monthly on a specific day and time
     * @param string $time Format: "HH:MM"
     */
    public function monthlyOn(int $dayOfMonth = 1, string $time = '0:00'): self
    {
        [$hour, $minute] = explode(':', $time);
        $hour = (int) $hour;
        $minute = (int) $minute;
        $this->expression = "{$minute} {$hour} {$dayOfMonth} * *";
        return $this;
    }

    /**
     * Run the task twice monthly
     */
    public function twiceMonthly(int $firstDay = 1, int $secondDay = 16, string $time = '0:00'): self
    {
        [$hour, $minute] = explode(':', $time);
        $hour = (int) $hour;
        $minute = (int) $minute;
        $this->expression = "{$minute} {$hour} {$firstDay},{$secondDay} * *";
        return $this;
    }

    /**
     * Run the task quarterly
     */
    public function quarterly(): self
    {
        $this->expression = '0 0 1 1-12/3 *';
        return $this;
    }

    /**
     * Run the task yearly
     */
    public function yearly(): self
    {
        $this->expression = '0 0 1 1 *';
        return $this;
    }

    /**
     * Run the task on weekdays
     */
    public function weekdays(): self
    {
        $this->expression = '0 0 * * 1-5';
        return $this;
    }

    /**
     * Run the task on weekends
     */
    public function weekends(): self
    {
        $this->expression = '0 0 * * 0,6';
        return $this;
    }

    /**
     * Run the task on Mondays
     */
    public function mondays(): self
    {
        return $this->days(1);
    }

    /**
     * Run the task on Tuesdays
     */
    public function tuesdays(): self
    {
        return $this->days(2);
    }

    /**
     * Run the task on Wednesdays
     */
    public function wednesdays(): self
    {
        return $this->days(3);
    }

    /**
     * Run the task on Thursdays
     */
    public function thursdays(): self
    {
        return $this->days(4);
    }

    /**
     * Run the task on Fridays
     */
    public function fridays(): self
    {
        return $this->days(5);
    }

    /**
     * Run the task on Saturdays
     */
    public function saturdays(): self
    {
        return $this->days(6);
    }

    /**
     * Run the task on Sundays
     */
    public function sundays(): self
    {
        return $this->days(0);
    }

    /**
     * Run task on specific days
     * @param int|array<int, string> $days
     */
    public function days($days): self
    {
        $days = is_array($days) ? implode(',', $days) : $days;
        $this->expression = "0 0 * * {$days}";
        return $this;
    }

    /**
     * Set the time for the task
     * @param string $time Format: "HH:MM"
     */
    public function at(string $time): self
    {
        [$hour, $minute] = explode(':', $time);
        $hour = (int) $hour;
        $minute = (int) $minute;

        // Replace hour and minute in existing expression
        $parts = explode(' ', $this->expression ?? '');
        $parts[0] = (string) $minute;
        $parts[1] = (string) $hour;
        $this->expression = implode(' ', $parts);

        return $this;
    }

    /**
     * Set custom cron expression
     */
    public function cron(string $expression): self
    {
        $this->expression = $expression;
        return $this;
    }

    /**
     * Set the callback for the task
     */
    public function call(callable $callback): self
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * Build and return the CronTask
     * @throws CronException
     */
    public function build(): CronTask
    {
        if ($this->callback === null) {
            throw new CronException("Task '{$this->name}' must have a callback. Use call() method.");
        }

        if ($this->expression === null) {
            throw new CronException("Task '{$this->name}' must have a schedule. Use methods like daily(), hourly(), etc.");
        }

        return new CronTask($this->name, $this->expression, $this->callback);
    }

    /**
     * Get the cron expression
     */
    public function getExpression(): ?string
    {
        return $this->expression;
    }
}
