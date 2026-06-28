<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages;

use Quantum\App\Contracts\BootStageInterface;
use Quantum\App\AppContext;

/**
 * Class InitDebuggerStage
 * @package Quantum\App
 */
class InitDebuggerStage implements BootStageInterface
{
    public function process(AppContext $context): void
    {
        debugbar()->initStore();
    }
}
