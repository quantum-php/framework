<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Middleware;

use Quantum\Middleware\Exceptions\MiddlewareException;
use Quantum\RateLimit\RateLimitMiddleware;
use Quantum\App\Exceptions\BaseException;
use Quantum\Router\MatchedRoute;
use Quantum\Router\Route;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class MiddlewareManager
 * @package Quantum\Middleware
 */
class MiddlewareManager
{
    /**
     * Middlewares queue
     * @var array<string>
     */
    private array $middlewares = [];

    /**
     * Current module
     */
    private ?string $module;

    private Route $route;

    private bool $hasRateLimit;

    public function __construct(MatchedRoute $matchedRoute)
    {
        $this->route = $matchedRoute->getRoute();
        $this->middlewares = array_values($this->route->getMiddlewares() ?? []);
        $this->module = $this->route->getModule();
        $this->hasRateLimit = $this->route->getRateLimit() !== null;
    }

    /**
     * Apply Middlewares.
     * @throws MiddlewareException|BaseException
     */
    public function applyMiddlewares(Request $request, Closure $terminal): Response
    {
        return $this->applyFrameworkMiddlewares(
            $request,
            fn (Request $request): Response => $this->applyModuleMiddlewares($request, $terminal)
        );
    }

    /**
     * Apply framework-level middleware stage.
     */
    private function applyFrameworkMiddlewares(Request $request, Closure $next): Response
    {
        if (!$this->hasRateLimit) {
            return $next($request);
        }

        $middleware = new RateLimitMiddleware($this->route);
        return $middleware->apply($request, $next);
    }

    /**
     * Apply module middleware stage.
     * @throws MiddlewareException|BaseException
     */
    private function applyModuleMiddlewares(Request $request, Closure $terminal): Response
    {
        if (!current($this->middlewares)) {
            return $terminal($request);
        }

        $currentMiddleware = $this->getMiddleware($request);
        next($this->middlewares);

        return $currentMiddleware->apply($request, fn (Request $request): Response => $this->applyModuleMiddlewares($request, $terminal));
    }

    /**
     * Loads and gets the current middleware instance
     * @throws MiddlewareException|BaseException
     */
    private function getMiddleware(Request $request): Middleware
    {
        $middlewareClass = request()->getModuleBaseNamespace() . '\\' . $this->module . '\\Middlewares\\' . current($this->middlewares);

        if (!class_exists($middlewareClass)) {
            throw MiddlewareException::middlewareNotFound($middlewareClass);
        }

        $middleware = new $middlewareClass($request);

        if (!$middleware instanceof Middleware) {
            throw MiddlewareException::notInstanceOf($middlewareClass, Middleware::class);
        }

        return $middleware;
    }

}
