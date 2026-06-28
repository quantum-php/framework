<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Request;

use Quantum\Router\Route as RouterRoute;
use Quantum\Router\RouteCollection;
use Quantum\Router\MatchedRoute;
use Quantum\Di\Di;
use Closure;

/**
 * Trait Route
 * @package Quantum\Http
 */
trait Route
{
    private ?MatchedRoute $route = null;

    public function setMatchedRoute(?MatchedRoute $route): void
    {
        $this->route = $route;
    }

    public function getMatchedRoute(): ?MatchedRoute
    {
        return $this->route;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getCurrentMiddlewares(): ?array
    {
        return $this->route ? $this->route->getRoute()->getMiddlewares() : null;
    }

    public function getCurrentModule(): ?string
    {
        return $this->route ? $this->route->getRoute()->getModule() : null;
    }

    public function getCurrentController(): ?string
    {
        return $this->route ? $this->route->getRoute()->getController() : null;
    }

    public function getCurrentAction(): ?string
    {
        return $this->route ? $this->route->getRoute()->getAction() : null;
    }

    public function getRouteCallback(): ?Closure
    {
        return $this->route ? $this->route->getRoute()->getClosure() : null;
    }

    public function getCurrentRoutePattern(): ?string
    {
        return $this->route ? $this->route->getRoute()->getPattern() : null;
    }

    public function getCompiledRoutePattern(): ?string
    {
        return $this->route ? $this->route->getRoute()->getCompiledPattern() : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteParams(): array
    {
        return $this->route ? $this->route->getParams() : [];
    }

    /**
     * @return mixed|null
     */
    public function getRouteParam(string $name)
    {
        $params = $this->getRouteParams();

        return $params[$name] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRouteCacheSettings(): ?array
    {
        return $this->route ? $this->route->getRoute()->getCache() : null;
    }

    public function getRouteName(): ?string
    {
        return $this->route ? $this->route->getRoute()->getName() : null;
    }

    public function getRoutePrefix(): ?string
    {
        return $this->route ? $this->route->getRoute()->getPrefix() : null;
    }

    public function findRouteByName(string $name, string $module): ?RouterRoute
    {
        if (!Di::isRegistered(RouteCollection::class)) {
            return null;
        }

        $collection = Di::get(RouteCollection::class);

        foreach ($collection->all() as $route) {
            if (
                $route->getName() !== null &&
                strcasecmp($route->getName(), $name) === 0 &&
                strcasecmp((string) $route->getModule(), $module) === 0
            ) {
                return $route;
            }
        }

        return null;
    }

    public function routeGroupExists(string $name, string $module): bool
    {
        if (!Di::isRegistered(RouteCollection::class)) {
            return false;
        }

        $collection = Di::get(RouteCollection::class);

        foreach ($collection->all() as $route) {
            if (
                $route->getGroup() !== null &&
                strcasecmp($route->getGroup(), $name) === 0 &&
                strcasecmp((string) $route->getModule(), $module) === 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function getModuleBaseNamespace(): string
    {
        return environment()->isTesting()
            ? 'Quantum\\Tests\\_root\\modules'
            : 'Modules';
    }
}
