<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Request;

use Quantum\App\Enums\ReservedKeys;
use InvalidArgumentException;

/**
 * Trait Body
 * @package Quantum\Http
 */
trait Body
{
    /**
     * Request body
     * @var array<string, mixed>
     */
    private array $__request = [];

    /**
     * Checks if request contains a data by given key
     */
    public function has(string $key): bool
    {
        return isset($this->__request[$key]);
    }

    /**
     * Retrieves data from request by given key
     * @return mixed
     */
    public function get(string $key, ?string $default = null, bool $raw = false)
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->__request[$key];

        if ($raw) {
            return $value;
        }

        return is_array($value)
            ? array_map('strip_tags', $value)
            : strip_tags($value);
    }

    /**
     * Sets new key/value pair into request
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        if ($key === ReservedKeys::RENDERED_VIEW) {
            throw new InvalidArgumentException("Cannot set reserved key: `$key`");
        }

        $this->__request[$key] = $value;
    }

    /**
     * Gets all request parameters
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->__request, $this->__files);
    }

    /**
     * Deletes the element from request by given key
     */
    public function delete(string $key): void
    {
        if ($this->has($key)) {
            unset($this->__request[$key]);
        }
    }
}
