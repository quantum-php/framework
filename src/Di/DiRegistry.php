<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Di;

use Quantum\Di\Exceptions\DiException;
use ReflectionClass;

/**
 * DiRegistry Class
 *
 * Stores dependency bindings for the container.
 *
 * @package Quantum\Di
 */
class DiRegistry
{
    /**
     * @var array<string, class-string>
     */
    private array $dependencies = [];

    /**
     * Register dependencies
     * @param array<string, mixed> $dependencies
     * @throws DiException
     */
    public function registerDependencies(array $dependencies): void
    {
        foreach ($dependencies as $abstract => $concrete) {
            if (!$this->isRegistered($abstract)) {
                $this->register($concrete, $abstract);
            }
        }
    }

    /**
     * Registers new dependency
     * @throws DiException
     */
    public function register(string $concrete, ?string $abstract = null): void
    {
        $key = $abstract ?? $concrete;

        if (isset($this->dependencies[$key])) {
            throw DiException::dependencyAlreadyRegistered($key);
        }

        if (!class_exists($concrete)) {
            throw DiException::dependencyNotInstantiable($concrete);
        }

        if (!(new ReflectionClass($concrete))->isInstantiable()) {
            throw DiException::dependencyNotInstantiable($concrete);
        }

        if ($abstract !== null && !class_exists($abstract) && !interface_exists($abstract)) {
            throw DiException::invalidAbstractDependency($abstract);
        }

        $this->dependencies[$key] = $concrete;
    }

    /**
     * Checks if a dependency registered
     */
    public function isRegistered(string $abstract): bool
    {
        return isset($this->dependencies[$abstract]);
    }

    /**
     * @throws DiException
     * @return class-string
     */
    public function getConcrete(string $abstract): string
    {
        if (!$this->isRegistered($abstract)) {
            throw DiException::dependencyNotRegistered($abstract);
        }

        return $this->dependencies[$abstract];
    }

    public function reset(): void
    {
        $this->dependencies = [];
    }
}
