<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Environment\Traits;

use Quantum\Environment\Exceptions\EnvException;

/**
 * Trait ValueTrait
 * @package Quantum\Environment
 */
trait ValueTrait
{
    /**
     * Gets the environment variable value
     * @throws EnvException
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        if (!$this->loaded) {
            throw EnvException::environmentNotLoaded();
        }

        if (array_key_exists($key, $this->envContent)) {
            return $this->envContent[$key];
        }

        return $default;
    }

    /**
     * Gets the environment variable value as a boolean
     * @throws EnvException
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->getValue($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Gets the environment variable value as an integer
     * @throws EnvException
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->getValue($key, $default);

        if (is_int($value)) {
            return $value;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT);

        if ($number === false) {
            throw EnvException::invalidValue($key, is_scalar($value) ? (string) $value : '');
        }

        return $number;
    }

    /**
     * Checks if there is a such key
     */
    public function hasKey(string $key): bool
    {
        return array_key_exists($key, $this->envContent);
    }

    /**
     * Gets the row of .env file by given key
     */
    public function getRow(string $key): ?string
    {
        if (!array_key_exists($key, $this->envContent)) {
            return null;
        }

        return $key . '=' . $this->envContent[$key];
    }
}
