<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Cookie\Contracts;

/**
 * Interface CookieStorageInterface
 * @package Quantum\Cookie
 */
interface CookieStorageInterface
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
     * @return mixed|null
     */
    public function get(string $key);

    /**
     * Sets data by given key
     * @param mixed $value
     */
    public function set(string $key, $value = '', int $time = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false): void;

    /**
     * Deletes data by given key
     */
    public function delete(string $key, string $path = '/'): void;

    /**
     * Deletes whole storage data
     */
    public function flush(): void;
}
