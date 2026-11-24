<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config\Traits;

use Quantum\Config\Exceptions\ConfigException;

/**
 * Trait TypedValuesTrait
 * @package Quantum\Config
 */
trait TypedValuesTrait
{
    /**
     * Gets the value by the key, or the default when the key is missing
     * @param mixed $default
     * @return mixed
     */
    abstract public function get(string $key, $default = null);

    /**
     * Gets the value as a boolean; without a default, the key is required
     * @throws ConfigException
     */
    public function getBool(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default ?? throw ConfigException::missingKey($key);
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Gets the value as an integer; without a default, the key is required
     * @throws ConfigException
     */
    public function getInt(string $key, ?int $default = null): int
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default ?? throw ConfigException::missingKey($key);
        }

        if (is_int($value)) {
            return $value;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT);

        if ($number === false) {
            throw ConfigException::invalidValue($key, is_scalar($value) ? (string) $value : '');
        }

        return $number;
    }
}
