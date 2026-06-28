<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Di;

use Quantum\Di\Exceptions\DiException;
use Quantum\App\App;
use ReflectionException;

/**
 * Di Class
 *
 * Static facade that delegates all calls to the DiContainer
 * owned by AppContext. Preserves the existing static API
 * for full backward compatibility.
 *
 * @package Quantum\Di
 * @method static void registerDependencies(array<string, mixed> $dependencies)
 * @method static void register(string $concrete, ?string $abstract = null)
 * @method static bool isRegistered(string $abstract)
 * @method static bool has(string $abstract)
 * @method static void set(string $abstract, object $instance, bool $override = true)
 * @method static mixed get(string $dependency, array<mixed> $args = [])
 * @method static mixed create(string $dependency, array<mixed> $args = [])
 * @method static array<int, mixed> autowire(callable $entry, array<mixed> $args = [])
 * @method static void resetContainer()
 */
class Di
{
    /**
     * Gets the current container instance from AppContext
     */
    public static function getCurrent(): DiContainer
    {
        return App::getContext()->getContainer();
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     * @throws DiException|ReflectionException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if (!method_exists(self::getCurrent(), $method)) {
            throw DiException::invalidCallable($method);
        }

        return self::getCurrent()->$method(...$arguments);
    }
}
