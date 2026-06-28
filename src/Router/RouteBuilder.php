<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Router;

use Quantum\Router\Exceptions\RouteException;
use Closure;

/**
 * Class RouteBuilder
 * @internal Fluent DSL interpreter and route composition engine.
 * @package Quantum\Router
 */
final class RouteBuilder
{
    private RouteCollection $collection;

    private PatternCompiler $patternCompiler;

    private ?Route $currentRoute = null;

    private ?string $currentModule = null;

    private ?string $currentPrefix = null;

    private bool $inGroup = false;

    private ?string $currentGroupName = null;

    /**
     * @var array<int, Route>
     */
    private array $groupRoutes = [];

    /**
     * @var array<string, mixed>
     */
    private array $groupMiddlewares = [];

    /**
     * @var Route[]|null
     */
    private ?array $lastGroupRoutes = null;

    public function __construct()
    {
        $this->patternCompiler = new PatternCompiler();

        $this->collection = new RouteCollection();
    }

    /**
     * Execute DSL and return final RouteCollection
     * @param array<string, Closure> $moduleRouteClosures
     * @param array<string, array<string, mixed>> $moduleConfigs
     * @throws RouteException
     */
    public function build(array $moduleRouteClosures, array $moduleConfigs): RouteCollection
    {
        foreach ($moduleRouteClosures as $module => $closure) {
            if (!$closure instanceof Closure) {
                throw RouteException::moduleRoutesNotClosure($module);
            }

            $options = $moduleConfigs[$module] ?? [];

            $this->currentModule = $module;
            $this->currentPrefix = trim((string) ($options['prefix'] ?? ''), '/');

            $closure($this);

            $this->currentModule = null;
            $this->currentPrefix = null;
            $this->currentRoute = null;
            $this->lastGroupRoutes = null;
        }

        return $this->collection;
    }

    /**
     * Define a route with multiple HTTP methods.
     * @param callable|string $handler
     * @param string|null $action
     * @throws RouteException
     */
    public function add(string $path, string $methods, $handler, string $action = null): self
    {
        $methodList = array_filter(
            array_map('trim', explode('|', $methods)),
            static fn ($m): bool => $m !== ''
        );

        return $this->addRoute($methodList, $path, $handler, $action);
    }

    /**
     * Define a GET route.
     * @param callable|string $handler
     * @param string|null $action
     * @throws RouteException
     */
    public function get(string $path, $handler, string $action = null): self
    {
        return $this->addRoute(['GET'], $path, $handler, $action);
    }

    /**
     * Define a POST route.
     * @param callable|string $handler
     * @param string|null $action
     * @throws RouteException
     */
    public function post(string $path, $handler, string $action = null): self
    {
        return $this->addRoute(['POST'], $path, $handler, $action);
    }

    /**
     * Define a PUT route.
     * @param callable|string $handler
     * @param string|null $action
     * @throws RouteException
     */
    public function put(string $path, $handler, string $action = null): self
    {
        return $this->addRoute(['PUT'], $path, $handler, $action);
    }

    /**
     * Define a DELETE route.
     * @param callable|string $handler
     * @throws RouteException
     */
    public function delete(string $path, $handler, string $action = null): self
    {
        return $this->addRoute(['DELETE'], $path, $handler, $action);
    }

    /**
     * Group routes under a shared name and configuration.
     * @return $this
     * @throws RouteException
     */
    public function group(string $name, callable $callback): self
    {
        if ($this->inGroup) {
            throw RouteException::nestedGroups();
        }

        $this->lastGroupRoutes = null;
        $this->currentGroupName = $name;
        $this->inGroup = true;
        $this->groupRoutes = [];
        $this->groupMiddlewares = [];

        $callback($this);

        /** @phpstan-ignore-next-line */
        foreach ($this->groupRoutes as $route) {
            $route->addMiddlewares($this->groupMiddlewares);
        }

        $this->lastGroupRoutes = $this->groupRoutes;

        $this->inGroup = false;
        $this->groupRoutes = [];
        $this->groupMiddlewares = [];
        $this->currentGroupName = null;
        $this->currentRoute = null;

        return $this;
    }

    /**
     * Apply middlewares to the current route or group.
     * @param array<string> $middlewares
     * @return $this
     * @throws RouteException
     */
    public function middlewares(array $middlewares): self
    {
        if ($this->currentRoute !== null) {
            $this->currentRoute->addMiddlewares($middlewares);
            return $this;
        }

        if ($this->inGroup) {
            $this->groupMiddlewares = array_merge(
                $this->groupMiddlewares,
                $middlewares
            );

            return $this;
        }

        if ($this->lastGroupRoutes !== null) {
            foreach ($this->lastGroupRoutes as $route) {
                $route->addMiddlewares($middlewares);
            }

            return $this;
        }

        throw RouteException::middlewaresOutsideRoute();
    }

    /**
     * Assign a unique name to the current route.
     * @return $this
     * @throws RouteException
     */
    public function name(string $name): self
    {
        if ($this->currentRoute === null) {
            throw RouteException::nameBeforeDefinition();
        }
        $currentModule = $this->currentModule;

        foreach ($this->collection->all() as $route) {
            if ($route->getName() === null) {
                continue;
            }

            if ($route->getName() === $name && $route->getModule() === $currentModule) {
                throw RouteException::nonUniqueNameInModule($name);
            }
        }

        $this->currentRoute->name($name);
        return $this;
    }

    /**
     * Enable or disable caching for the current route or group.
     * @return $this
     * @throws RouteException
     */
    public function cacheable(bool $enabled, ?int $ttl = null): self
    {
        if ($this->inGroup) {
            foreach ($this->groupRoutes as $route) {
                $route->cache($enabled, $ttl);
            }

            return $this;
        }

        if ($this->lastGroupRoutes !== null) {
            foreach ($this->lastGroupRoutes as $route) {
                $route->cache($enabled, $ttl);
            }

            return $this;
        }

        if ($this->currentRoute !== null) {
            $this->currentRoute->cache($enabled, $ttl);
            return $this;
        }

        throw RouteException::cacheableOutsideRoute();
    }

    /**
     * Configure rate limiting for the current route or group.
     * @return $this
     * @throws RouteException
     */
    public function rateLimit(int $limit, int $interval): self
    {
        if ($this->inGroup) {
            foreach ($this->groupRoutes as $route) {
                $route->rateLimit($limit, $interval);
            }

            return $this;
        }

        if ($this->lastGroupRoutes !== null) {
            foreach ($this->lastGroupRoutes as $route) {
                $route->rateLimit($limit, $interval);
            }

            return $this;
        }

        if ($this->currentRoute !== null) {
            $this->currentRoute->rateLimit($limit, $interval);
            return $this;
        }

        throw RouteException::rateLimitOutsideRoute();
    }

    /**
     * Create and register a Route instance.
     * @param array<string> $methods
     * @param callable|string $handler
     * @throws RouteException
     */
    private function addRoute(
        array $methods,
        string $path,
        $handler,
        ?string $action
    ): self {
        if ($methods === []) {
            throw RouteException::noHttpMethods();
        }

        $this->lastGroupRoutes = null;
        $pattern = $this->resolvePath($path);

        if ($handler instanceof Closure) {
            $route = new Route(
                $methods,
                $pattern,
                null,
                null,
                $handler
            );
        } else {
            if (!is_string($handler) || $action === null) {
                throw RouteException::incompleteControllerRoute();
            }

            if (!str_contains($handler, '\\')) {
                if ($this->currentModule === null) {
                    throw RouteException::controllerWithoutModule();
                }

                $handler =
                    request()->getModuleBaseNamespace()
                    . '\\'
                    . $this->currentModule
                    . '\\Controllers\\'
                    . $handler;
            }

            $route = new Route(
                $methods,
                $pattern,
                $handler,
                $action
            );
        }

        [$compiled, $params] = $this->patternCompiler->compile($route);

        $route
            ->module($this->currentModule)
            ->prefix($this->currentPrefix)
            ->group($this->currentGroupName)
            ->setCompiledPattern($compiled);

        $this->collection->add($route);
        $this->currentRoute = $route;

        if ($this->inGroup) {
            $this->groupRoutes[] = $route;
        }

        return $this;
    }

    /**
     * Resolve path using the current prefix.
     */
    private function resolvePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        if ($this->currentPrefix === null || $this->currentPrefix === '') {
            return $path;
        }

        return '/' . trim($this->currentPrefix . $path, '/');
    }
}
