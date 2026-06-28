<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Hook;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Hook\Exceptions\HookException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Loader\Setup;
use ReflectionException;

/**
 * Class HookManager
 * @package Quantum\Hook
 */
class HookManager
{
    /**
     * Core hooks
     */
    public const CORE_HOOKS = [];

    /**
     * Registered hooks store
     * @var array<string, array<int, callable>>
     */
    private array $store = [];

    /**
     * @throws HookException|ConfigException|DiException|LoaderException|ReflectionException
     */
    public function __construct()
    {
        if (!config()->has('hooks')) {
            config()->import(new Setup('config', 'hooks'));
        }

        $registeredHooks = array_merge(self::CORE_HOOKS, config()->get('hooks') ?: []);

        foreach ($registeredHooks as $hookName) {
            $this->register($hookName);
        }
    }

    /**
     * Adds a new listener for a given hook
     * @throws HookException
     */
    public function on(string $name, callable $function): void
    {
        if (!$this->exists($name)) {
            throw HookException::unregisteredHookName($name);
        }

        $this->store[$name][] = $function;
    }

    /**
     * Fires the hook
     * @param array<mixed>|null $args
     * @throws HookException
     */
    public function fire(string $name, ?array $args = null): void
    {
        if (!$this->exists($name)) {
            throw HookException::unregisteredHookName($name);
        }

        foreach ($this->store[$name] as $index => $fn) {
            unset($this->store[$name][$index]);
            $fn($args);
        }
    }

    /**
     * Gets all registered hooks
     * @return array<string, array<int, callable>>
     */
    public function getRegistered(): array
    {
        return $this->store;
    }

    /**
     * Registers new hook
     * @throws HookException
     */
    protected function register(string $name): void
    {
        if ($this->exists($name)) {
            throw HookException::hookDuplicateName($name);
        }

        $this->store[$name] = [];
    }

    /**
     * Checks if hooks registered
     */
    protected function exists(string $name): bool
    {
        return array_key_exists($name, $this->store);
    }
}
