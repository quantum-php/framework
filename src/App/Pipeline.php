<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App;

use Quantum\App\Contracts\StageInterface;
use InvalidArgumentException;

/**
 * Class Pipeline
 *
 * Runs stages in order with a context. The event a stage declares in its BEFORE / AFTER
 * constants is dispatched around it, with the context as the payload.
 * A stage that stops the context ends the run: no later stage runs or dispatches events.
 * The optional closing stage runs last, also after a stop, but not after a stage throws.
 *
 * @template TContext of AppContext
 * @package Quantum\App
 */
final readonly class Pipeline
{
    /**
     * @var array<StageInterface<TContext>>
     */
    private array $stages;

    /**
     * @var StageInterface<TContext>|null
     */
    private ?StageInterface $closingStage;

    /**
     * @param array<StageInterface<TContext>> $stages
     * @param StageInterface<TContext>|null $closingStage
     */
    public function __construct(array $stages = [], ?StageInterface $closingStage = null)
    {
        foreach ($stages as $stage) {
            if (!$stage instanceof StageInterface) {
                throw new InvalidArgumentException('All stages must implement ' . StageInterface::class);
            }
        }

        $this->stages = $stages;
        $this->closingStage = $closingStage;
    }

    /**
     * @param TContext $context
     */
    public function run(AppContext $context): void
    {
        foreach ($this->stages as $stage) {
            $this->runStage($stage, $context);

            if ($context->isStopped()) {
                break;
            }
        }

        if ($this->closingStage !== null) {
            $this->runStage($this->closingStage, $context);
        }
    }

    /**
     * @param StageInterface<TContext> $stage
     * @param TContext $context
     */
    private function runStage(StageInterface $stage, AppContext $context): void
    {
        $this->dispatchStageEvent($stage, 'BEFORE', $context);

        $stage->process($context);

        $this->dispatchStageEvent($stage, 'AFTER', $context);
    }

    /**
     * Dispatches the event a stage declares for the given point, if any.
     * Only runtime stages declare events, and they run after the helpers are loaded.
     * @param StageInterface<TContext> $stage
     * @param TContext $context
     */
    private function dispatchStageEvent(StageInterface $stage, string $point, AppContext $context): void
    {
        $constant = $stage::class . '::' . $point;

        if (!defined($constant)) {
            return;
        }

        event()->dispatch((string) constant($constant), ['context' => $context]);
    }
}
