<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Model\Traits;

use Quantum\Paginator\Exceptions\PaginatorException;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Model\ModelCollection;
use Quantum\Paginator\Paginator;
use Quantum\Model\DbModel;

/**
 * Trait SoftDeletes
 * @package Quantum\Model
 */
trait SoftDeletes
{
    protected bool $includeTrashed = false;

    /**
     * Soft delete the model by setting the deleted_at timestamp.
     * @throws ModelException
     */
    public function delete(): bool
    {
        $this->prop($this->getDeletedAtColumn(), $this->getSoftDeleteTimestampValue());
        return $this->save();
    }

    /**
     * Restore a soft deleted model.
     * @throws ModelException
     */
    public function restore(): bool
    {
        $this->prop($this->getDeletedAtColumn(), null);
        return $this->save();
    }

    /**
     * Force delete the model from the database.
     * @throws ModelException
     */
    public function forceDelete(): bool
    {
        return parent::delete();
    }

    /**
     * Include soft deleted records in the query.
     * @return static
     */
    public function withTrashed(): self
    {
        $this->includeTrashed = true;

        return $this;
    }

    /**
     * Return only soft deleted records.
     * @return static
     * @throws ModelException
     */
    public function onlyTrashed(): self
    {
        $this->includeTrashed = true;

        $this->getOrmInstance()->isNotNull($this->getDeletedAtColumn());

        return $this;
    }

    /**
     * Get all non-deleted records unless withTrashed is called.
     * @throws BaseException
     */
    public function get(): ModelCollection
    {
        $this->applySoftDeleteScope();

        return parent::get();
    }

    /**
     * Paginate non-deleted records unless withTrashed is called.
     * @throws BaseException
     * @throws PaginatorException
     */
    public function paginate(int $perPage, int $currentPage = 1): Paginator
    {
        $this->applySoftDeleteScope();

        return parent::paginate($perPage, $currentPage);
    }

    /**
     * Count all non-deleted records unless withTrashed() is called.
     * @throws ModelException
     */
    public function count(): int
    {
        $this->applySoftDeleteScope();

        return parent::count();
    }

    /**
     * Find one record by its ID, excluding soft deleted unless withTrashed() is called.
     * @throws BaseException
     */
    public function findOne(int $id): ?DbModel
    {
        $this->applySoftDeleteScope();

        return parent::findOne($id);
    }

    /**
     * Find one record by column and value, excluding soft deleted unless withTrashed() is called.
     * @param mixed $value
     * @throws ModelException|BaseException
     */
    public function findOneBy(string $column, $value): ?DbModel
    {
        $this->applySoftDeleteScope();

        return parent::findOneBy($column, $value);
    }

    /**
     * Get the first record, excluding soft deleted unless withTrashed() is called.
     * @throws BaseException
     */
    public function first(): ?DbModel
    {
        $this->applySoftDeleteScope();

        return parent::first();
    }

    /**
     * Apply soft delete scope to the current query if not including trashed.
     * @throws ModelException
     */
    protected function applySoftDeleteScope(): void
    {
        if (!$this->includeTrashed) {
            $this->getOrmInstance()->isNull($this->getDeletedAtColumn());
        }
    }

    /**
     * Returns timestamp value for soft delete based on model timestamp config (if available).
     * @return int|string
     */
    protected function getSoftDeleteTimestampValue()
    {
        if (method_exists($this, 'nowTimestampValue')) {
            return $this->nowTimestampValue();
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * Get the column name used for soft deletes.
     */
    protected function getDeletedAtColumn(): string
    {
        if (defined(static::class . '::DELETED_AT')) {
            return static::DELETED_AT;
        }

        return 'deleted_at';
    }
}
