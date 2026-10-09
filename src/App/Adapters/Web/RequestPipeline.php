<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web;

use Quantum\App\Contracts\RequestStageInterface;
use InvalidArgumentException;

/**
 * Class RequestPipeline
 *
 * Runs the web request stages in order. The pipeline does not skip stages by itself;
 * a stage checks RequestContext::isStopped() when it must be skipped.
 * Stage lifecycle events are not dispatched here yet.
 *
 * @package Quantum\App
 */
class RequestPipeline
{
    /**
     * @var RequestStageInterface[]
     */
    private array $stages;

    /**
     * @param RequestStageInterface[] $stages
     */
    public function __construct(array $stages = [])
    {
        foreach ($stages as $stage) {
            if (!$stage instanceof RequestStageInterface) {
                throw new InvalidArgumentException(
                    'All stages must implement ' . RequestStageInterface::class
                );
            }
        }

        $this->stages = $stages;
    }

    public function run(RequestContext $context): void
    {
        foreach ($this->stages as $stage) {
            $stage->process($context);
        }
    }
}
