<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Console;

/**
 * Class RegisterCoreCommandsStage
 * @package Quantum\App
 */
class RegisterCoreCommandsStage extends RegisterCommandsStage
{
    protected function getDirectory(): string
    {
        return framework_dir() . DS . 'Console' . DS . 'Commands';
    }

    protected function getNamespace(): string
    {
        return '\\Quantum\\Console\\Commands\\';
    }
}
