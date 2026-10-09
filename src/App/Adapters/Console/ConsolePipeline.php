<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Console;

use Quantum\App\Contracts\ConsoleStageInterface;
use InvalidArgumentException;

/**
 * Class ConsolePipeline
 *
 * Runs the console stages in order.
 * Stage lifecycle events are not dispatched here yet.
 *
 * @package Quantum\App
 */
class ConsolePipeline
{
    /**
     * @var ConsoleStageInterface[]
     */
    private array $stages;

    /**
     * @param ConsoleStageInterface[] $stages
     */
    public function __construct(array $stages = [])
    {
        foreach ($stages as $stage) {
            if (!$stage instanceof ConsoleStageInterface) {
                throw new InvalidArgumentException(
                    'All stages must implement ' . ConsoleStageInterface::class
                );
            }
        }

        $this->stages = $stages;
    }

    public function run(ConsoleContext $context): void
    {
        foreach ($this->stages as $stage) {
            $stage->process($context);
        }
    }
}
