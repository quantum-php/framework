<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Sleekdb\Statements;

use SleekDB\Exceptions\InvalidConfigurationException;
use Quantum\Database\Exceptions\DatabaseException;
use SleekDB\Exceptions\InvalidArgumentException;
use Quantum\Database\Contracts\DbalInterface;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use SleekDB\Exceptions\IOException;
use SleekDB\QueryBuilder;

/**
 * Trait Result
 * @package Quantum\Database
 */
trait Result
{
    abstract protected function resetBuilderState(): void;

    /**
     * @param array<int, array<string, mixed>> $results
     * @return array<int, array<string, mixed>>
     */
    abstract protected function applyRelatedCriteriaPostFilter(array $results): array;

    /**
     * @inheritDoc
     */
    public function get(): array
    {
        try {
            $results = $this->fetchFilteredResultsFromBuilder($this->getBuilder());

            return array_map(function ($element): object {
                $item = clone $this;
                $item->resetBuilderState();
                $item->updateOrmModel($element);
                return $item;
            }, $results);
        } finally {
            $this->resetBuilderState();
        }
    }

    /**
     * @inheritDoc
     * @throws BaseException
     * @throws DatabaseException
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws InvalidConfigurationException
     * @throws ModelException
     */
    public function findOne(int $id): DbalInterface
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(['id', '=', $id]);
            $results = $this->fetchFilteredResultsFromBuilder($builder);
            $result = $results[0] ?? [];
            $this->updateOrmModel($result);
        } finally {
            $this->resetBuilderState();
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws BaseException
     * @throws DatabaseException
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws InvalidConfigurationException
     * @throws ModelException
     */
    public function findOneBy(string $column, $value): DbalInterface
    {
        try {
            $builder = $this->getBuilder();
            $builder->where([$column, '=', $value]);
            $results = $this->fetchFilteredResultsFromBuilder($builder);
            $result = $results[0] ?? [];
            $this->updateOrmModel($result);
        } finally {
            $this->resetBuilderState();
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws ModelException|DatabaseException|BaseException|IOException|InvalidArgumentException|InvalidConfigurationException
     */
    public function first(): DbalInterface
    {
        try {
            $results = $this->fetchFilteredResultsFromBuilder($this->getBuilder());
            $result = $results[0] ?? [];
            $this->updateOrmModel($result);
        } finally {
            $this->resetBuilderState();
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        try {
            $results = $this->fetchFilteredResultsFromBuilder($this->getBuilder());
            return count($results);
        } finally {
            $this->resetBuilderState();
        }
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        $result = $this->data ?: [];

        if (count($this->hidden) > 0 && count($result)) {
            $result = $this->setHidden($result);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function setHidden(array $result): array
    {
        return array_diff_key($result, array_flip($this->hidden));
    }

    /**
     * Fetches results from given builder and applies post-fetch filters.
     * @param QueryBuilder $builder
     * @return array<int, array<string, mixed>>
     */
    protected function fetchFilteredResultsFromBuilder(QueryBuilder $builder): array
    {
        return $this->applyRelatedCriteriaPostFilter($builder->getQuery()->fetch());
    }
}
