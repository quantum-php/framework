<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Console;

use Symfony\Component\Console\Command\Command;
use Quantum\App\Contracts\ConsoleStageInterface;
use Quantum\Console\CommandDiscovery;
use Quantum\App\Adapters\Console\ConsoleContext;

/**
 * Class RegisterCommandsStage
 *
 * Discovers the commands of a directory and adds them to the console application.
 *
 * @package Quantum\App
 */
abstract class RegisterCommandsStage implements ConsoleStageInterface
{
    public function process(ConsoleContext $context): void
    {
        foreach (CommandDiscovery::discover($this->getDirectory(), $this->getNamespace()) as $command) {
            $instance = new $command['class']();

            if ($instance instanceof Command) {
                $context->getApplication()->add($instance);
            }
        }
    }

    abstract protected function getDirectory(): string;

    abstract protected function getNamespace(): string;
}
