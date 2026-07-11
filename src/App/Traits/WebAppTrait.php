<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Traits;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Router\Exceptions\RouteException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\ResourceCache\ViewCache;
use Quantum\Router\RouteCollection;
use Quantum\Router\MatchedRoute;
use Quantum\Router\RouteFinder;
use Quantum\Debugger\Debugger;
use Quantum\Http\Response;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Di\Di;
use Exception;

/**
 * Trait WebAppTrait
 * @package Quantum\App
 */
trait WebAppTrait
{
    /**
     * @throws RouteException|BaseException
     */
    private function resolveRoute(): ?MatchedRoute
    {
        $routeFinder = new RouteFinder(Di::get(RouteCollection::class));

        $matchedRoute = $routeFinder->find(request());

        if ($matchedRoute === null) {
            return null;
        }

        request()->setMatchedRoute($matchedRoute);

        return $matchedRoute;
    }

    /**
     * @throws DiException|ReflectionException
     */
    private function logDebugInfo(): void
    {
        $debugbar = debugbar();

        if ($debugbar->isEnabled()) {
            $debugbar->addToStoreCell(Debugger::HOOKS, 'info', hook()->getRegistered());
        }
    }

    /**
     * @throws ConfigException|DiException|ReflectionException|LoaderException
     */
    private function setupViewCache(): ViewCache
    {
        if (!Di::isRegistered(ViewCache::class)) {
            Di::register(ViewCache::class);
        }

        $viewCache = Di::get(ViewCache::class);

        if ($viewCache->isEnabled()) {
            $viewCache->setup();
        }

        return $viewCache;
    }

    /**
     * @throws ConfigException|LoaderException|DiException|ReflectionException
     */
    private function handleCors(Response $response): void
    {
        if (!config()->has('cors')) {
            config()->import(new Setup('config', 'cors'));
        }

        foreach (config()->get('cors') as $key => $value) {
            $response->setHeader($key, (string) $value);
        }
    }

    /**
     * @throws ConfigException|LoaderException|DiException|ReflectionException|Exception
     */
    private function sendResponse(Response $response): void
    {
        try {
            $this->handleCors($response);
            $response->send();
        } finally {
            $this->cleanupRequestContext();
        }
    }

    /**
     * Clears request-scoped state after the response has been sent.
     */
    private function cleanupRequestContext(): void
    {
        request()->setMatchedRoute(null);
        request()->flush();
    }
}
