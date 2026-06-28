<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Router;

use Quantum\Http\Request;

/**
 * Class RouteFinder
 * @internal Resolves an incoming request to a matched route.
 * @package Quantum\Router
 */
final class RouteFinder
{
    private PatternCompiler $patternCompiler;

    private RouteCollection $routes;

    public function __construct(RouteCollection $routes)
    {
        $this->patternCompiler = new PatternCompiler();
        $this->routes = $routes;
    }

    /**
     * Find the first route that matches request method and URI.
     * @throws Exceptions\RouteException
     */
    public function find(Request $request): ?MatchedRoute
    {
        $method = $request->getMethod() ?? '';
        $uri = $request->getUri() ?? '';

        foreach ($this->routes->all() as $route) {
            if (!$route->allowsMethod($method)) {
                continue;
            }

            if (!$this->patternCompiler->match($route, $uri)) {
                continue;
            }

            $params = $this->patternCompiler->getParams();

            return new MatchedRoute($route, $params);
        }

        return null;
    }
}
