<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Model;

use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use IteratorAggregate;
use Countable;
use Generator;

/**
 * Class ModelCollection
 * @package Quantum\Model
 * @template TKey of array-key
 * @template TValue of Model
 * @implements IteratorAggregate<TKey, TValue>
 */
class ModelCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<TKey, TValue>
     */
    private array $models = [];

    /**
     * @var iterable<TKey, TValue>
     */
    private iterable $originalModels;

    private bool $modelsProcessed = false;

    /**
     * @param iterable<TKey, TValue> $models
     * @throws BaseException
     */
    public function __construct(iterable $models = [])
    {
        $this->originalModels = $models;

        if (is_array($models)) {
            $this->processModels();
        }
    }

    /**
     * Add a model to the collection
     * @param TValue $model
     * @return $this
     * @throws BaseException
     */
    public function add(Model $model): self
    {
        $this->processModels();

        $this->models[] = $model;

        if (!is_array($this->originalModels)) {
            $this->originalModels = $this->models;
        } else {
            $this->originalModels[] = $model;
        }

        return $this;
    }

    /**
     * Remove a model from the collection
     * @param TValue $model
     * @return $this
     * @throws BaseException
     */
    public function remove(Model $model): self
    {
        $this->processModels();

        $this->models = array_filter($this->models, fn (Model $m): bool => $m !== $model);

        $this->originalModels = $this->models;

        return $this;
    }

    /**
     * Get all models as an array
     * @return Model[]
     * @throws BaseException
     */
    public function all(): array
    {
        $this->processModels();
        return $this->models;
    }

    /**
     * Get the count of models in the collection
     * @throws BaseException
     */
    public function count(): int
    {
        $this->processModels();
        return count($this->models);
    }

    /**
     * Get the first model in the collection
     * @return Model|null
     * @throws BaseException
     */
    public function first(): ?Model
    {
        foreach ($this->getIterator() as $model) {
            return $model;
        }

        return null;
    }

    /**
     * Get the last model in the collection
     * @return Model|null
     * @throws BaseException
     */
    public function last(): ?Model
    {
        $this->processModels();
        return empty($this->models) ? null : end($this->models);
    }

    /**
     * Check if the collection is empty
     * @throws BaseException
     */
    public function isEmpty(): bool
    {
        return !$this->first() instanceof Model;
    }

    /**
     * Get an iterator for the collection
     * @throws BaseException
     */
    public function getIterator(): Generator
    {
        if ($this->modelsProcessed) {
            yield from $this->models;
        } else {
            foreach ($this->originalModels as $model) {
                $this->validateModel($model);
                yield $model;
            }

            $this->processModels();
        }
    }

    /**
     * Process models from original source into the internal array
     * @throws BaseException
     */
    private function processModels(): void
    {
        if ($this->modelsProcessed) {
            return;
        }

        $this->models = [];

        foreach ($this->originalModels as $model) {
            $this->validateModel($model);
            $this->models[] = $model;
        }

        $this->modelsProcessed = true;
    }

    /**
     * Validate that an item is a Model instance
     * @param mixed $model
     * @throws BaseException
     */
    private function validateModel($model): void
    {
        if (!$model instanceof Model) {
            throw ModelException::notInstanceOf(
                get_debug_type($model),
                Model::class
            );
        }
    }
}
