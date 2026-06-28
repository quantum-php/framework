<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Di\Exceptions\DiException;
use Quantum\Router\Route;
use Quantum\Http\Request;
use Quantum\Di\Di;

/**
 * Gets current route middlewares
 * @return array<int|string, mixed>|null
 * @throws DiException|ReflectionException
 */
function current_middlewares(): ?array
{
    return request()->getCurrentMiddlewares();
}

/**
 * Gets current route module
 * @throws DiException|ReflectionException
 */
function current_module(): ?string
{
    if (!Di::has(Request::class)) {
        return null;
    }

    return request()->getCurrentModule();
}

/**
 * Gets current route controller
 * @throws DiException|ReflectionException
 */
function current_controller(): ?string
{
    return request()->getCurrentController();
}

/**
 * Gets current route action
 * @throws DiException|ReflectionException
 */
function current_action(): ?string
{
    return request()->getCurrentAction();
}

/**
 * Gets current route callback
 * @throws DiException|ReflectionException
 */
function route_callback(): ?Closure
{
    return request()->getRouteCallback();
}

/**
 * Gets current route DSL pattern
 * @return string|null
 * @throws DiException|ReflectionException
 */
function current_route(): ?string
{
    return request()->getCurrentRoutePattern();
}

/**
 * Gets current route compiled pattern
 * @throws DiException|ReflectionException
 */
function route_pattern(): ?string
{
    return request()->getCompiledRoutePattern();
}

/**
 * Gets current route params
 * @return array<string, mixed>
 * @throws DiException|ReflectionException
 */
function route_params(): array
{
    return request()->getRouteParams();
}

/**
 * Gets route parameter by name
 * @param string $name
 * @return mixed|null
 * @throws DiException|ReflectionException
 */
function route_param(string $name)
{
    return request()->getRouteParam($name);
}

/**
 * Gets current route method
 * @return string
 * @throws DiException|ReflectionException
 */
function route_method(): string
{
    return request()->getMethod() ?? '';
}

/**
 * Gets the current route uri
 * @return string|null
 * @throws DiException|ReflectionException
 */
function route_uri(): ?string
{
    return request()->getUri();
}

/**
 * Gets the current route cache settings
 * @return array<string, mixed>|null
 * @throws DiException|ReflectionException
 */
function route_cache_settings(): ?array
{
    return request()->getRouteCacheSettings();
}

/**
 * Gets the current route name
 * @throws DiException|ReflectionException
 */
function route_name(): ?string
{
    return request()->getRouteName();
}

/**
 * Gets the current route name
 * @throws DiException|ReflectionException
 */
function route_prefix(): ?string
{
    return request()->getRoutePrefix();
}

/**
 * Finds the route by name in given module scope
 * @throws DiException|ReflectionException
 */
function find_route_by_name(string $name, string $module): ?Route
{
    return request()->findRouteByName($name, $module);
}

/**
 * Checks the existence of the route group by name in given module scope
 * @throws DiException|ReflectionException
 */
function route_group_exists(string $name, string $module): bool
{
    return request()->routeGroupExists($name, $module);
}

/**
 * Gets the module base namespace depending on env
 * @throws DiException|ReflectionException
 */
function module_base_namespace(): string
{
    return request()->getModuleBaseNamespace();
}
