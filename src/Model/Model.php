<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Model;

use Quantum\Model\Exceptions\ModelException;

/**
 * Class Model
 * @package Quantum\Model
 */
abstract class Model
{
    /**
     * Internal attributes
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Models fillable properties
     * @var array<string>
     */
    protected array $fillable = [];

    /**
     * Models hidden properties
     * Used by DBAL and plain models
     * @var array<string>
     */
    public array $hidden = [];

    /**
     * @param mixed $value
     * @return $this|mixed|null
     */
    public function prop(string $key, $value = null)
    {
        if (func_num_args() === 1) {
            return $this->attributes[$key] ?? null;
        }

        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Fill object properties
     * @param array<string, mixed> $props
     * @return $this
     * @throws ModelException
     */
    public function fill(array $props): self
    {
        foreach ($props as $key => $value) {
            if (!$this->shouldFill($key)) {
                throw ModelException::inappropriateProperty($key);
            }

            $this->attributes[$key] = $value;
        }

        return $this;
    }

    /**
     * Converts to array
     * @return array<string, mixed>
     */
    public function asArray(): array
    {
        if (!$this->hidden) {
            return $this->attributes;
        }

        return array_diff_key(
            $this->attributes,
            array_flip($this->hidden)
        );
    }

    /**
     * Checks if model is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->asArray());
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->prop($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->prop($key, $value);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    protected function shouldFill(string $key): bool
    {
        return in_array($key, $this->fillable, true);
    }
}
