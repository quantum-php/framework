<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web;

use Quantum\ResourceCache\ViewCache;
use Quantum\Router\MatchedRoute;
use Quantum\App\AppContext;
use Quantum\Http\Response;

/**
 * Class RequestContext
 *
 * Carries the state of a single web request through the request stages.
 * Unlike AppContext, which is immutable, this object is mutated by the stages.
 *
 * @package Quantum\App
 */
class RequestContext
{
    private AppContext $appContext;

    private ?MatchedRoute $matchedRoute = null;

    private ?Response $response = null;

    private ?ViewCache $viewCache = null;

    private bool $stopped = false;

    public function __construct(AppContext $appContext)
    {
        $this->appContext = $appContext;
    }

    public function getAppContext(): AppContext
    {
        return $this->appContext;
    }

    public function getMatchedRoute(): ?MatchedRoute
    {
        return $this->matchedRoute;
    }

    public function setMatchedRoute(?MatchedRoute $matchedRoute): void
    {
        $this->matchedRoute = $matchedRoute;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getViewCache(): ?ViewCache
    {
        return $this->viewCache;
    }

    public function setViewCache(ViewCache $viewCache): void
    {
        $this->viewCache = $viewCache;
    }

    /**
     * Marks the request as finished early, so stages that compute a response skip their work.
     * The stage that sends the response still runs.
     */
    public function stop(): void
    {
        $this->stopped = true;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }
}
