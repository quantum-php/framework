<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config\Traits;

use Quantum\Config\ConfigSection;
use Dflydev\DotAccessData\Data;

/**
 * Trait ReadTrait
 *
 * Reading the config values in $this->configs, shared by Config and ConfigSection.
 *
 * @package Quantum\Config
 */
trait ReadTrait
{
    /**
     * Gets the value by the key, or the default when the key is missing
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if ($this->configs instanceof Data && $this->configs->has($key)) {
            return $this->configs->get($key);
        }

        return $default;
    }

    /**
     * Checks if the key exists
     */
    public function has(string $key): bool
    {
        return $this->configs instanceof Data && $this->configs->has($key);
    }

    /**
     * Gets all values as an array
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->configs instanceof Data ? $this->configs->export() : [];
    }

    /**
     * Gets the values under the key as a new, read-only section; empty when the value is missing or not an array
     */
    public function section(string $key): ConfigSection
    {
        $value = $this->get($key);

        return new ConfigSection(is_array($value) ? $value : []);
    }
}
