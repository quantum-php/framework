<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config\Contracts;

use Quantum\Config\ConfigSection;
use Quantum\Config\Setup;

/**
 * Interface ConfigInterface
 * @package Quantum\Config
 */
interface ConfigInterface
{
    /**
     * Checks if the storage contains a key
     */
    public function has(string $key): bool;

    /**
     * Gets the value from the storage by given key
     * @param mixed $default
     * @return mixed|null
     */
    public function get(string $key, $default = null);

    /**
     * Gets the values under the key as a new, read-only section; empty when the value is missing or not an array
     */
    public function section(string $key): ConfigSection;

    /**
     * Gets all values as an array
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Sets storage value with the given key
     * @param mixed $value
     */
    public function set(string $key, $value): void;

    /**
     * Deletes the data from the storage by given key
     */
    public function delete(string $key): void;

    /**
     * Deletes whole storage data
     */
    public function flush(): void;

    /**
     * Loads configuration
     */
    public function load(Setup $setup): void;

    /**
     * Imports new config file
     */
    public function import(Setup $setup): void;

    /**
     * Imports new config file when it exists
     */
    public function importIfExists(Setup $setup): bool;
}
