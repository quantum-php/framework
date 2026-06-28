<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth;

/**
 * Class User
 * @package Quantum\Auth
 */
class User
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Set Data
     * @param array<string, mixed> $data
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get Data
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get Fields
     * @return array<int, string>
     */
    public function getFields(): array
    {
        return array_keys($this->data);
    }

    /**
     * Set Fields
     * @param array<string, mixed> $schema
     * @return $this
     */
    public function setFields(array $schema): self
    {
        foreach ($schema as $field) {
            if (isset($field['name'])) {
                $this->data[$field['name']] = '';
            }
        }

        return $this;
    }

    /**
     * Get Field Value
     */
    public function getFieldValue(string $field): ?string
    {
        return $this->hasField($field) ? $this->data[$field] : null;
    }

    /**
     * Set Fields Value
     * @return $this
     */
    public function setFieldValue(string $field, ?string $value): self
    {
        $this->data[$field] = $value;
        return $this;
    }

    /**
     * Has Field
     */
    public function hasField(string $field): bool
    {
        return array_key_exists($field, $this->data);
    }

    /**
     * Gets the user property
     * @return string|null
     */
    public function __get(string $property): mixed
    {
        return $this->getFieldValue($property);
    }
}
