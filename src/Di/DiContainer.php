<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Di;

use Quantum\Di\Exceptions\DiException;
use ReflectionException;
use ReflectionParameter;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionClass;
use Closure;

/**
 * DiContainer Class
 *
 * Instance-based dependency injection container.
 * Holds all dependency registrations and resolved instances for a single application execution.
 *
 * @package Quantum\Di
 */
class DiContainer
{
    private DiRegistry $registry;

    /**
     * @var array<string, object>
     */
    private array $container = [];

    /**
     * @var array<string, bool>
     */
    private array $resolving = [];

    public function __construct(?DiRegistry $registry = null)
    {
        $this->registry = $registry ?? new DiRegistry();
    }

    /**
     * Register dependencies
     * @param array<string, mixed> $dependencies
     * @throws DiException
     */
    public function registerDependencies(array $dependencies): void
    {
        $this->registry->registerDependencies($dependencies);
    }

    /**
     * Registers new dependency
     * @throws DiException
     */
    public function register(string $concrete, ?string $abstract = null): void
    {
        $this->registry->register($concrete, $abstract);
    }

    /**
     * Checks if a dependency registered
     */
    public function isRegistered(string $abstract): bool
    {
        return $this->registry->isRegistered($abstract);
    }

    /**
     * Checks if an instance exists in the container
     */
    public function has(string $abstract): bool
    {
        return isset($this->container[$abstract]);
    }

    /**
     * Sets an instance into container
     * @template T of object
     * @param class-string<T> $abstract
     * @param T $instance
     * @throws DiException
     */
    public function set(string $abstract, object $instance, bool $override = true): void
    {
        if (!class_exists($abstract) && !interface_exists($abstract)) {
            throw DiException::invalidAbstractDependency($abstract);
        }

        if (!is_a($instance, $abstract)) {
            throw DiException::invalidAbstractDependency($abstract);
        }

        if (isset($this->container[$abstract])) {
            throw DiException::dependencyAlreadyRegistered($abstract);
        }

        if (!$override && $this->registry->isRegistered($abstract)) {
            throw DiException::dependencyAlreadyRegistered($abstract);
        }

        if (!$this->registry->isRegistered($abstract)) {
            $this->registry->register($instance::class, $abstract);
        }

        $this->container[$abstract] = $instance;
    }

    /**
     * Retrieves a shared instance of the given dependency.
     * @template T of object
     * @param class-string<T> $dependency
     * @param array<mixed> $args
     * @return T
     * @throws DiException|ReflectionException
     */
    public function get(string $dependency, array $args = [])
    {
        if (!$this->isRegistered($dependency)) {
            throw DiException::dependencyNotRegistered($dependency);
        }

        return $this->resolve($dependency, $args, true);
    }

    /**
     * Creates new instance of the given dependency.
     * @template T of object
     * @param class-string<T> $dependency
     * @param array<mixed> $args
     * @return T
     * @throws DiException|ReflectionException
     */
    public function create(string $dependency, array $args = [])
    {
        if (!$this->isRegistered($dependency)) {
            $this->register($dependency);
        }

        return $this->resolve($dependency, $args, false);
    }

    /**
     * Autowire callable parameters
     * @param array<mixed> $args
     * @return array<int, mixed>
     * @throws DiException|ReflectionException
     */
    public function autowire(callable $entry, array $args = []): array
    {
        if ($entry instanceof Closure) {
            $reflection = new ReflectionFunction($entry);
        } elseif (is_array($entry)) {
            [$target, $method] = $entry;
            $reflection = new ReflectionMethod($target, $method);
        } else {
            throw DiException::invalidCallable();
        }

        return $this->resolveParameters($reflection->getParameters(), $args);
    }

    public function reset(): void
    {
        $this->registry->reset();
        $this->resetContainer();
    }

    public function resetContainer(): void
    {
        $this->container = [];
        $this->resolving = [];
    }

    /**
     * Resolves the dependency
     * @param array<mixed> $args
     * @return mixed|object
     * @throws DiException|ReflectionException
     */
    private function resolve(string $abstract, array $args = [], bool $singleton = true)
    {
        $this->checkCircularDependency($abstract);
        $this->resolving[$abstract] = true;

        try {
            $concrete = $this->registry->getConcrete($abstract);

            if ($singleton) {
                if (!isset($this->container[$abstract])) {
                    $this->container[$abstract] = $this->instantiate($concrete, $args);
                }
                return $this->container[$abstract];
            }

            return $this->instantiate($concrete, $args);

        } finally {
            unset($this->resolving[$abstract]);
        }
    }

    /**
     * Instantiates the dependency
     * @param class-string $concrete
     * @param array<mixed> $args
     * @return mixed
     * @throws ReflectionException|DiException
     */
    private function instantiate(string $concrete, array $args = [])
    {
        $class = new ReflectionClass($concrete);
        $constructor = $class->getConstructor();

        $params = $constructor
            ? $this->resolveParameters($constructor->getParameters(), $args)
            : [];

        return new $concrete(...$params);
    }

    /**
     * Resolve parameter list
     * @param array<ReflectionParameter> $parameters
     * @param array<mixed> $args
     * @return array<mixed>
     * @throws DiException|ReflectionException
     */
    private function resolveParameters(array $parameters, array &$args = []): array
    {
        $resolved = [];

        foreach ($parameters as $param) {
            $resolved[] = $this->resolveParameter($param, $args);
        }

        return $resolved;
    }

    /**
     * Resolve single parameter
     * @param array<mixed> $args
     * @return array|mixed|object|null
     * @throws DiException|ReflectionException
     */
    private function resolveParameter(ReflectionParameter $param, array &$args = [])
    {
        $type = null;

        if ($param->getType() instanceof \ReflectionNamedType) {
            $type = $param->getType()->getName();
        }

        if ($type !== null && $this->registry->isRegistered($type)) {
            /** @var class-string $type */
            return $this->get($type);
        }

        if ($type !== null && $this->instantiable($type)) {
            /** @var class-string $type */
            return $this->create($type);
        }

        if ($type === 'array') {
            return $args;
        }

        if ($args !== []) {
            return array_shift($args);
        }

        return $param->isDefaultValueAvailable()
            ? $param->getDefaultValue()
            : null;
    }

    /**
     * Checks if the class is instantiable
     */
    private function instantiable(string $class): bool
    {
        return class_exists($class)
            && (new ReflectionClass($class))->isInstantiable();
    }

    /**
     * @throws DiException
     */
    private function checkCircularDependency(string $abstract): void
    {
        if (isset($this->resolving[$abstract])) {
            $chain = implode(' -> ', array_keys($this->resolving)) . ' -> ' . $abstract;
            throw DiException::circularDependency($chain);
        }
    }
}
