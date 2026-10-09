<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Contracts;

use Quantum\App\Adapters\Web\RequestContext;

/**
 * Interface RequestStageInterface
 * @package Quantum\App
 */
interface RequestStageInterface
{
    /**
     * Processes a single request stage
     */
    public function process(RequestContext $context): void;
}
