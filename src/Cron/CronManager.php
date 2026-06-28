<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cron;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Cron\Contracts\CronTaskInterface;
use Quantum\Logger\Factories\LoggerFactory;
use Quantum\Cron\Exceptions\CronException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Logger\Enums\LoggerType;
use ReflectionException;
use Throwable;

/**
 * Class CronManager
 * @package Quantum\Cron
 */
class CronManager
{
    /**
     * Loaded tasks
     * @var array<string, CronTaskInterface>
     */
    private array $tasks = [];

    /**
     * Cron directory path
     * @var string
     */
    private $cronDirectory;

    /**
     * Execution statistics
     * @var array<string, int>
     */
    private array $stats = [
        'total' => 0,
        'executed' => 0,
        'skipped' => 0,
        'failed' => 0,
        'locked' => 0,
    ];

    /**
     * @throws DiException|ReflectionException
    */
    public function __construct(?string $cronDirectory = null)
    {
        $configuredPath = $cronDirectory ?? cron_config('path');
        $this->cronDirectory = $configuredPath ?: $this->getDefaultCronDirectory();
    }

    /**
     * Load tasks from the cron directory
     * @throws CronException|BaseException|ConfigException|DiException|ReflectionException
     */
    public function loadTasks(): void
    {
        if (!fs()->isDirectory($this->cronDirectory)) {
            if ($this->cronDirectory !== $this->getDefaultCronDirectory()) {
                throw CronException::cronDirectoryNotFound($this->cronDirectory);
            }
            return;
        }

        $files = fs()->glob($this->cronDirectory . DS . '*.php') ?: [];

        foreach ($files as $file) {
            $this->loadTaskFromFile($file);
        }

        $this->stats['total'] = count($this->tasks);
    }

    /**
     * @throws BaseException|ConfigException|CronException|DiException|ReflectionException
     */
    private function loadTaskFromFile(string $file): void
    {
        $task = fs()->require($file);

        if (is_array($task)) {
            $task = $this->createTaskFromArray($task);
        }

        if (!$task instanceof CronTaskInterface) {
            throw CronException::invalidTaskFile($file);
        }

        $this->tasks[$task->getName()] = $task;
    }

    /**
     * Create a task from array definition
     * @param array<string, mixed> $definition
     * @throws CronException
     */
    private function createTaskFromArray(array $definition): CronTask
    {
        if (!isset($definition['name'], $definition['expression'], $definition['callback'])) {
            throw new CronException('Task definition must contain name, expression, and callback');
        }

        return new CronTask(
            $definition['name'],
            $definition['expression'],
            $definition['callback']
        );
    }

    /**
     * Run all due tasks
     * @param bool $force Ignore locks
     * @return array Statistics
     */
    /**
     * @return array|int[]
     * @throws CronException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function runDueTasks(bool $force = false): array
    {
        $this->loadTasks();

        foreach ($this->tasks as $task) {
            if ($task->shouldRun()) {
                $this->runTask($task, $force);
            } else {
                $this->stats['skipped']++;
            }
        }

        return $this->stats;
    }

    /**
     * Run a specific task by name
     * @throws CronException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function runTaskByName(string $taskName, bool $force = false): void
    {
        $this->loadTasks();

        if (!isset($this->tasks[$taskName])) {
            throw CronException::taskNotFound($taskName);
        }

        $this->runTask($this->tasks[$taskName], $force);
    }

    /**
     * Run a single task
     * @throws CronException|ConfigException|DiException|BaseException|ReflectionException
     */
    private function runTask(CronTaskInterface $task, bool $force = false): void
    {
        $lock = new CronLock($task->getName());

        if (!$force && !$lock->acquire()) {
            $this->stats['locked']++;
            $this->log('warning', "Task \"{$task->getName()}\" skipped: locked");
            return;
        }

        $startTime = microtime(true);
        $this->log('info', "Task \"{$task->getName()}\" started");

        try {
            $task->handle();
            $duration = round(microtime(true) - $startTime, 2);
            $this->stats['executed']++;
            $this->log('info', "Task \"{$task->getName()}\" completed in {$duration}s");
        } catch (Throwable $e) {
            $this->stats['failed']++;
            $this->log('error', "Task \"{$task->getName()}\" failed: " . $e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        } finally {
            if (!$force) {
                $lock->release();
            }
        }
    }

    /**
     * Get all loaded tasks
     * @return array<string, CronTaskInterface>
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Get execution statistics
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Get default cron directory
     */
    private function getDefaultCronDirectory(): string
    {
        return base_dir() . DS . 'cron';
    }

    /**
     * Log a message
     * @param array<string, mixed> $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        try {
            $logger = LoggerFactory::get(LoggerType::SINGLE);
            $logger->log($level, '[CRON] ' . $message, $context);
        } catch (Throwable) {
            error_log(sprintf('[CRON] [%s] %s', strtoupper($level), $message));
        }
    }
}
