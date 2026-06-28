<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cron;

use Quantum\Cron\Contracts\CronTaskInterface;
use Quantum\Cron\Exceptions\CronException;
use Cron\CronExpression;
use Exception;

/**
 * Class CronTask
 * @package Quantum\Cron
 */
class CronTask implements CronTaskInterface
{
    /**
     * Cron expression instance
     */
    private CronExpression $cronExpression;

    /**
     * Task name
     */
    private string $name;

    /**
     * Task callback
     * @var callable
     */
    private $callback;

    /**
     * CronTask constructor
     * @throws CronException
     */
    public function __construct(string $name, string $expression, callable $callback)
    {
        $this->name = $name;
        $this->callback = $callback;

        try {
            $this->cronExpression = new CronExpression($expression);
        } catch (Exception) {
            throw CronException::invalidExpression($expression);
        }
    }

    /**
     * @inheritDoc
     */
    public function getExpression(): string
    {
        return $this->cronExpression->getExpression() ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function shouldRun(): bool
    {
        return $this->cronExpression->isDue();
    }

    /**
     * @inheritDoc
     */
    public function handle(): void
    {
        call_user_func($this->callback);
    }

    /**
     * Get the next run date
     */
    public function getNextRunDate(): \DateTime
    {
        return $this->cronExpression->getNextRunDate();
    }

    /**
     * Get the previous run date
     * @throws Exception
     */
    public function getPreviousRunDate(): \DateTime
    {
        return $this->cronExpression->getPreviousRunDate();
    }
}
