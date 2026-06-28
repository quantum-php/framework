<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Traits;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use Quantum\Console\CommandDiscovery;
use ReflectionException;
use Exception;

/**
 * Class ConsoleAppTrait
 * @package Quantum\App
 */
trait ConsoleAppTrait
{
    public function createApplication(string $name, string $version): Application
    {
        return new Application($name, $version);
    }

    /**
     * @throws ReflectionException
     */
    private function registerCoreCommands(): void
    {
        $directory = framework_dir() . DS . 'Console' . DS . 'Commands';
        $namespace = '\\Quantum\\Console\\Commands\\';

        $this->registerCommands($directory, $namespace);
    }

    /**
     * @throws ReflectionException
     */
    private function registerAppCommands(): void
    {
        $directory = base_dir() . DS . 'shared' . DS . 'Commands';
        $namespace = '\\Shared\\Commands\\';

        $this->registerCommands($directory, $namespace);
    }

    /**
     * @throws ReflectionException
     */
    private function registerCommands(string $directory, string $namespace): void
    {
        $commands = CommandDiscovery::discover($directory, $namespace);

        foreach ($commands as $command) {
            $instance = new $command['class']();
            if ($instance instanceof Command) {
                $this->application->add($instance);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function validateCommand(): void
    {
        $commandName = $this->input->getFirstArgument();

        if ($commandName === null || !$this->application->has($commandName)) {
            throw new Exception("Command `$commandName` is not defined");
        }
    }
}
