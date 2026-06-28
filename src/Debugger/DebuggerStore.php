<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Debugger;

use Quantum\Debugger\Contracts\DebuggerStoreInterface;

/**
 * Class DebuggerStore
 * @package Quantum\Debugger
 */
class DebuggerStore implements DebuggerStoreInterface
{
    /**
     * @var array<string, mixed>
     */
    private static array $store = [];

    /**
     * @inheritDoc
     */
    public function init(array $keys): void
    {
        foreach ($keys as $key) {
            if (!isset(self::$store[$key])) {
                self::$store[$key] = [];
            }
        }
    }

    /**
     * @inheritDoc
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return self::$store;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return isset(self::$store[$key]);
    }

    /**
     * @inheritDoc
     * @return array<mixed>
     */
    public function get(string $key): array
    {
        return self::$store[$key] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value): void
    {
        if (is_array($value)) {
            foreach ($value as $level => $data) {
                self::$store[$key][] = [$level => $data];
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        if ($this->has($key)) {
            self::$store[$key] = [];
        }
    }

    /**
     * @inheritDoc
     */
    public function flush(): void
    {
        self::$store = [];
    }
}
