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
 * Class ValidateCommandStage
 * @package Quantum\App
 */
class ValidateCommandStage implements ConsoleStageInterface
{
    /**
     * @throws Exception
     */
    public function process(ConsoleContext $context): void
    {
        $commandName = $context->getInput()->getFirstArgument();

        if ($commandName === null || !$context->getApplication()->has($commandName)) {
            throw new Exception("Command `$commandName` is not defined");
        }
    }
}
