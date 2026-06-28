<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App;

use Quantum\App\Contracts\BootStageInterface;
use InvalidArgumentException;

/**
 * Class BootPipeline
 * @package Quantum\App
 */
class BootPipeline
{
    /**
     * @var BootStageInterface[]
     */
    private array $stages;

    /**
     * @param BootStageInterface[] $stages
     */
    public function __construct(array $stages = [])
    {
        foreach ($stages as $stage) {
            if (!$stage instanceof BootStageInterface) {
                throw new InvalidArgumentException(
                    'All stages must implement ' . BootStageInterface::class
                );
            }
        }

        $this->stages = $stages;
    }

    public function run(AppContext $context): void
    {
        foreach ($this->stages as $stage) {
            $stage->process($context);
        }
    }
}
