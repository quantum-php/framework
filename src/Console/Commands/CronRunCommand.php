<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Cron\Exceptions\CronException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Console\CliCommand;
use Quantum\Cron\CronManager;
use ReflectionException;
use Throwable;

/**
 * Class CronRunCommand
 * @package Quantum\Console
 */
class CronRunCommand extends CliCommand
{
    /**
     * The console command name.
     */
    protected ?string $name = 'cron:run';

    /**
     * The console command description.
     */
    protected ?string $description = 'Run scheduled cron tasks';

    /**
     * Command help text.
     */
    protected ?string $help = 'Executes scheduled tasks defined in the cron directory. Use --task to run a single task or --force to bypass locks.';

    /**
     * Command options
     * @var array<int, list<string|null>>
     */
    protected array $options = [
        ['force', 'f', 'none', 'Force run tasks ignoring locks'],
        ['task', 't', 'optional', 'Run a specific task by name'],
        ['path', 'p', 'optional', 'Custom cron directory path'],
    ];

    /**
     * Executes the command
     */
    public function exec(): void
    {
        $force = (bool) $this->getOption('force');
        $taskName = $this->getOption('task');
        $cronPath = $this->getOption('path') ?: cron_config('path');

        try {
            $manager = new CronManager($cronPath);

            if ($taskName) {
                $this->runSpecificTask($manager, $taskName, $force);
            } else {
                $this->runAllDueTasks($manager, $force);
            }
        } catch (CronException $e) {
            $this->error($e->getMessage());
        } catch (Throwable $e) {
            $this->error('Unexpected error: ' . $e->getMessage());
        }
    }

    /**
     * Run all due tasks
     * @throws CronException|ConfigException|DiException|BaseException|ReflectionException
     */
    private function runAllDueTasks(CronManager $manager, bool $force): void
    {
        $this->info('Running scheduled tasks...');

        $stats = $manager->runDueTasks($force);

        $this->output('');
        $this->info('Execution Summary:');
        $this->output("  Total tasks: {$stats['total']}");
        $this->output("  Executed: <info>{$stats['executed']}</info>");
        $this->output("  Skipped: {$stats['skipped']}");

        if ($stats['locked'] > 0) {
            $this->output("  Locked: <comment>{$stats['locked']}</comment>");
        }

        if ($stats['failed'] > 0) {
            $this->output("  Failed: <error>{$stats['failed']}</error>");
        }

        $this->output('');

        if ($stats['executed'] > 0) {
            $this->info('✓ Tasks completed successfully');
        } elseif ($stats['total'] === 0) {
            $this->comment('No tasks found in cron directory');
        } else {
            $this->comment('No tasks were due to run');
        }
    }

    /**
     * Run a specific task
     * @throws CronException|ConfigException|DiException|BaseException|ReflectionException
     */
    private function runSpecificTask(CronManager $manager, string $taskName, bool $force): void
    {
        $this->info("Running task: {$taskName}");

        $manager->runTaskByName($taskName, $force);

        $stats = $manager->getStats();

        if ($stats['executed'] > 0) {
            $this->info("✓ Task '{$taskName}' completed successfully");
        } elseif ($stats['failed'] > 0) {
            $this->error("✗ Task '{$taskName}' failed");
        } elseif ($stats['locked'] > 0) {
            $this->comment("⚠ Task '{$taskName}' is locked");
        }
    }
}
