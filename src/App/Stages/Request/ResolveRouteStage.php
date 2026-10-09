<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Request;

use Quantum\App\Contracts\RequestStageInterface;
use Quantum\Router\Exceptions\RouteException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Router\RouteCollection;
use Quantum\App\Adapters\Web\RequestContext;
use Quantum\Router\RouteFinder;
use Quantum\Di\Di;

/**
 * Class ResolveRouteStage
 * @package Quantum\App
 */
class ResolveRouteStage implements RequestStageInterface
{
    /**
     * Finds the route for the current request and stores it on the context and the request
     * @throws RouteException|BaseException
     */
    public function process(RequestContext $context): void
    {
        if ($context->isStopped()) {
            return;
        }

        $matchedRoute = (new RouteFinder(Di::get(RouteCollection::class)))->find(request());

        if ($matchedRoute === null) {
            return;
        }

        request()->setMatchedRoute($matchedRoute);
        $context->setMatchedRoute($matchedRoute);
    }
}
