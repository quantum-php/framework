<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Contracts;

use Quantum\App\Adapters\Console\ConsoleContext;

/**
 * Interface ConsoleStageInterface
 * @package Quantum\App
 */
interface ConsoleStageInterface
{
    /**
     * Processes a single console stage
     */
    public function process(ConsoleContext $context): void;
}
