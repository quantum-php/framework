<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use ReflectionException;

/**
 * Class CommandRegistrar
 *
 * Adds the commands discovered in a directory to the console application.
 *
 * @package Quantum\Console
 */
class CommandRegistrar
{
    /**
     * @throws ReflectionException
     */
    public static function register(Application $application, string $directory, string $namespace): void
    {
        foreach (CommandDiscovery::discover($directory, $namespace) as $command) {
            $instance = new $command['class']();

            if ($instance instanceof Command) {
                $application->addCommand($instance);
            }
        }
    }
}
