<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Request;

use Quantum\App\Contracts\RequestStageInterface;
use Quantum\Http\Enums\StatusCode;
use Quantum\App\Adapters\Web\RequestContext;

/**
 * Class HandlePreflightStage
 * @package Quantum\App
 */
class HandlePreflightStage implements RequestStageInterface
{
    /**
     * Answers an OPTIONS request with an empty response and stops the remaining stages
     */
    public function process(RequestContext $context): void
    {
        if (!request()->isMethod('OPTIONS')) {
            return;
        }

        $context->setResponse(response()->setStatusCode(StatusCode::NO_CONTENT));
        $context->stop();
    }
}
