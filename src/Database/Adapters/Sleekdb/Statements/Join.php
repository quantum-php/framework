<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Sleekdb\Statements;

use Quantum\Database\Adapters\Sleekdb\SleekDbal;
use SleekDB\Exceptions\InvalidArgumentException;
use Quantum\Database\Contracts\DbalInterface;
use Quantum\Model\Exceptions\ModelException;
use Quantum\Database\Enums\Relation;
use Quantum\Model\DbModel;
use SleekDB\QueryBuilder;
use RuntimeException;

/**
 * Trait Join
 * @package Quantum\Database
 */
trait Join
{
    abstract protected function buildJoinPath(string $currentPath, string $table): string;

    /**
     * @inheritDoc
     */
    public function joinTo(DbModel $model, bool $switch = true): DbalInterface
    {
        $this->joins[] = [
            'model' => serialize($model),
            'switch' => $switch,
        ];

        return $this;
    }

    /**
     * Starts to apply joins
     * @throws ModelException
     */
    private function applyJoins(): void
    {
        if (!empty($this->joins)) {
            if ($this->queryBuilder === null) {
                throw new RuntimeException('Cannot apply joins without an initialized query builder.');
            }

            $this->applyJoin($this->queryBuilder, $this, $this->joins[0], 1, '');
        }
    }

    /**
     * Apply the join to query builder
     * @param array<string, mixed> $nextItem
     * @throws ModelException
     */
    private function applyJoin(
        QueryBuilder $queryBuilder,
        SleekDbal $currentItem,
        array $nextItem,
        int $level = 1,
        string $currentPath = ''
    ): QueryBuilder {
        $modelToJoin = unserialize($nextItem['model']);
        $switch = $nextItem['switch'];

        if (!$modelToJoin instanceof DbModel) {
            throw new RuntimeException('Failed to unserialize join model.');
        }

        $joinPath = $this->buildJoinPath($currentPath, $modelToJoin->table);

        $queryBuilder->join(function ($item) use ($currentItem, $modelToJoin, $switch, $level, $joinPath) {

            $sleekModel = new self(
                $modelToJoin->table,
                $modelToJoin::class,
                $modelToJoin->idColumn,
                $modelToJoin->relations()
            );

            $newQueryBuilder = $sleekModel->getOrmModel()->createQueryBuilder();

            $this->applyJoinTo($newQueryBuilder, $modelToJoin, $currentItem, $item);
            $this->applyRelatedCriteriaToJoin($newQueryBuilder, $joinPath);

            if ($switch && isset($this->joins[$level])) {
                $this->applyJoin($newQueryBuilder, $sleekModel, $this->joins[$level], $level + 1, $joinPath);
            }

            return $newQueryBuilder;

        }, $modelToJoin->table);

        if (!$switch && isset($this->joins[$level])) {
            $this->applyJoin($queryBuilder, $currentItem, $this->joins[$level], $level + 1, $currentPath);
        }

        return $queryBuilder;
    }

    /**
     * Applies criteria assigned to a specific join path on that join subquery.
     */
    private function applyRelatedCriteriaToJoin(QueryBuilder $queryBuilder, string $joinPath): void
    {
        if (!isset($this->relatedCriteriasByPath[$joinPath])) {
            return;
        }

        foreach ($this->relatedCriteriasByPath[$joinPath] as $criteria) {
            $queryBuilder->where($criteria);
        }
    }

    /**
     * Apply join condition for JOINTO type
     * @param array<string, mixed> $currentItem
     * @throws InvalidArgumentException
     * @throws ModelException
     */
    private function applyJoinTo(QueryBuilder $queryBuilder, DbModel $relatedModel, SleekDbal $currentModel, array $currentItem): void
    {
        $relation = $this->getValidatedRelation($currentModel, $relatedModel);

        match ($relation['type']) {
            Relation::HAS_ONE, Relation::HAS_MANY => $this->applyHasRelation($queryBuilder, $currentItem, $relation),
            Relation::BELONGS_TO => $this->applyBelongsTo($queryBuilder, $currentItem, $relation, $currentModel),
            default => throw ModelException::unsupportedRelationType($relation['type']),
        };
    }

    /**
     * @param array<string, mixed> $currentItem
     * @param array<string, mixed> $relation
     * @throws InvalidArgumentException
     */
    private function applyHasRelation(QueryBuilder $queryBuilder, array $currentItem, array $relation): void
    {
        $queryBuilder->where([
            $relation['foreign_key'],
            '=',
            $currentItem[$relation['local_key']],
        ]);
    }

    /**
     * @param array<string, mixed> $currentItem
     * @param array<string, mixed> $relation
     * @throws InvalidArgumentException
     * @throws ModelException
     */
    private function applyBelongsTo(QueryBuilder $queryBuilder, array $currentItem, array $relation, SleekDbal $currentModel): void
    {
        if (!isset($currentItem[$relation['foreign_key']])) {
            throw ModelException::missingForeignKeyValue($currentModel->getModelName(), $relation['foreign_key']);
        }

        $queryBuilder->where([
            $relation['local_key'],
            '=',
            $currentItem[$relation['foreign_key']],
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws ModelException
     */
    private function getValidatedRelation(SleekDbal $currentModel, DbModel $relatedModel): array
    {
        $relations = $currentModel->getForeignKeys();
        $relatedModelName = $relatedModel::class;

        if (!isset($relations[$relatedModelName])) {
            throw ModelException::wrongRelation($currentModel->getModelName(), $relatedModelName);
        }

        $relation = $relations[$relatedModelName];

        if (empty($relation['type'])) {
            throw ModelException::relationTypeMissing($currentModel->getModelName(), $relatedModelName);
        }

        if (empty($relation['foreign_key']) || empty($relation['local_key'])) {
            throw ModelException::missingRelationKeys($currentModel->getModelName(), $relatedModelName);
        }

        return $relation;
    }
}
