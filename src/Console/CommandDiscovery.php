<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console;

use ReflectionException;
use ReflectionClass;

/**
 * Class CommandDiscovery
 * @package Quantum\Console
 */
class CommandDiscovery
{
    /**
     * @throws ReflectionException
     * @return array<int, array<string, mixed>>
     */
    public static function discover(string $directory, string $namespace): array
    {
        $commands = [];

        foreach (get_directory_classes($directory) as $className) {
            $commandClass = $namespace . $className;

            if (!class_exists($commandClass)) {
                continue;
            }

            $commandReflection = new ReflectionClass($commandClass);

            if (!$commandReflection->isInstantiable() || !$commandReflection->isSubclassOf(CliCommand::class)) {
                continue;
            }

            $instance = $commandReflection->newInstance();

            $commands[] = [
                'class' => $commandClass,
                'name' => $instance->getName(),
                'description' => $instance->getDescription(),
                'help' => $instance->getHelp(),
            ];
        }

        return $commands;
    }
}
