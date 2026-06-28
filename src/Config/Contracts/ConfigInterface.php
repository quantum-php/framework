<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config\Contracts;

use Quantum\Loader\Setup;

/**
 * Interface ConfigInterface
 * @package Quantum\Config
 */
interface ConfigInterface
{
    /**
     * Gets whole storage data
     * @return mixed
     */
    public function all();

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
}
