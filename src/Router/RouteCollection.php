<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Router;

/**
 * Class RouteCollection
 * @internal Internal collection of Route descriptors.
 * @package Quantum\Router
 */
final class RouteCollection
{
    /**
     * @var Route[]
     */
    private array $routes = [];

    /**
     * Add a route to the collection.
     */
    public function add(Route $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * Return all routes in insertion order.
     * @return Route[]
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * Return total number of routes.
     */
    public function count(): int
    {
        return count($this->routes);
    }
}
