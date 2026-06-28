<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Cron\Exceptions\CronException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Cron\CronManager;
use Quantum\Cron\CronTask;
use Quantum\Cron\Schedule;
use Quantum\Loader\Setup;

if (!function_exists('cron_config')) {
    /**
     * Resolve cron configuration value
     * @param mixed $default
     * @return mixed
     * @throws DiException|ReflectionException
     */
    function cron_config(string $key, $default = null)
    {
        static $configLoaded = false;

        if (!$configLoaded) {
            try {
                if (!config()->has('cron')) {
                    config()->import(new Setup('config', 'cron'));
                }
            } catch (Throwable) {
                // Ignore missing cron config file and rely on defaults
            }

            $configLoaded = true;
        }

        return config()->get('cron.' . $key, $default);
    }
}

if (!function_exists('cron_manager')) {
    /**
     * Get CronManager instance
     */
    function cron_manager(?string $cronDirectory = null): CronManager
    {
        return new CronManager($cronDirectory);
    }
}

if (!function_exists('cron_task')) {
    /**
     * Create a new cron task
     * @throws CronException
     */
    function cron_task(string $name, string $expression, callable $callback): CronTask
    {
        return new CronTask($name, $expression, $callback);
    }
}

if (!function_exists('schedule')) {
    /**
     * Create a new schedule with fluent API
     */
    function schedule(string $name): Schedule
    {
        return new Schedule($name);
    }
}
