<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Model\Traits;

/**
 * Trait HasTimestamps
 *
 * @property array<string, mixed> $attributes
 * @property string $idColumn
 */
trait HasTimestamps
{
    /**
     * Column name for created timestamp
     */
    public string $createdAt = 'created_at';

    /**
     * Column name for updated timestamp
     */
    public string $updatedAt = 'updated_at';

    /**
     * Timestamp storage type: datetime|unix
     */
    public string $timestampType = 'datetime';

    /**
     * Determine whether the current model is new (insert) or existing (update).
     */
    protected function isNewRecord(): bool
    {
        $id = $this->attributes[$this->idColumn] ?? null;

        return $id === null || $id === '';
    }

    /**
     * Returns the current timestamp value based on model config.
     * Supports: datetime|unix
     * @return int|string
     */
    protected function nowTimestampValue(): int|string
    {
        if ($this->getTimestampType() === 'unix') {
            return time();
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * Get timestamp type.
     */
    protected function getTimestampType(): string
    {
        if (defined(static::class . '::TIMESTAMP_TYPE')) {
            return static::TIMESTAMP_TYPE;
        }

        return $this->timestampType;
    }

    /**
     * Get "created at" column name.
     */
    protected function getCreatedAtColumn(): string
    {
        if (defined(static::class . '::CREATED_AT')) {
            return static::CREATED_AT;
        }

        return $this->createdAt;
    }

    /**
     * Get "updated at" column name.
     */
    protected function getUpdatedAtColumn(): string
    {
        if (defined(static::class . '::UPDATED_AT')) {
            return static::UPDATED_AT;
        }

        return $this->updatedAt;
    }

    /**
     * Touch timestamps on save
     */
    protected function touchTimestamps(): void
    {
        $now = $this->nowTimestampValue();

        $createdAt = $this->getCreatedAtColumn();
        $updatedAt = $this->getUpdatedAtColumn();

        if ($this->isNewRecord()) {
            if (!array_key_exists($createdAt, $this->attributes)) {
                $this->attributes[$createdAt] = $now;
            }
        }

        $this->attributes[$updatedAt] = $now;
    }
}
