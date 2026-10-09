<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Console;

/**
 * Class RegisterAppCommandsStage
 * @package Quantum\App
 */
class RegisterAppCommandsStage extends RegisterCommandsStage
{
    protected function getDirectory(): string
    {
        return base_dir() . DS . 'shared' . DS . 'Commands';
    }

    protected function getNamespace(): string
    {
        return '\\Shared\\Commands\\';
    }
}
