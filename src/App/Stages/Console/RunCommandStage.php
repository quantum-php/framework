<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Console;

use Quantum\App\Contracts\ConsoleStageInterface;
use Quantum\App\Adapters\Console\ConsoleContext;
use Exception;

/**
 * Class RunCommandStage
 * @package Quantum\App
 */
class RunCommandStage implements ConsoleStageInterface
{
    /**
     * @throws Exception
     */
    public function process(ConsoleContext $context): void
    {
        $context->setExitCode(
            $context->getApplication()->run($context->getInput(), $context->getOutput())
        );
    }
}
