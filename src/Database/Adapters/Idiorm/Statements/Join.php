<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Idiorm\Statements;

use Quantum\Database\Adapters\Idiorm\IdiormPatch;
use Quantum\Database\Contracts\DbalInterface;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Database\Enums\Relation;
use Quantum\Model\DbModel;

/**
 * Trait Join
 * @package Quantum\Database
 */
trait Join
{
    /**
     * @inheritDoc
     * @throws BaseException
     */
    public function join(string $table, array $constraint, ?string $tableAlias = null): DbalInterface
    {
        $this->getOrmModel()->join($table, $constraint, $tableAlias);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws BaseException
     */
    public function innerJoin(string $table, array $constraint, ?string $tableAlias = null): DbalInterface
    {
        $this->getOrmModel()->inner_join($table, $constraint, $tableAlias);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws BaseException
     */
    public function leftJoin(string $table, array $constraint, ?string $tableAlias = null): DbalInterface
    {
        IdiormPatch::getInstance()
            ->use($this->getOrmModel())
            ->leftJoin($table, $constraint, $tableAlias);

        return $this;
    }

    /**
     * @inheritDoc
     * @throws BaseException
     */
    public function rightJoin(string $table, array $constraint, ?string $tableAlias = null): DbalInterface
    {
        IdiormPatch::getInstance()
            ->use($this->getOrmModel())
            ->rightJoin($table, $constraint, $tableAlias);

        return $this;
    }

    /**
     * @inheritDoc
     * @throws ModelException|BaseException
     */
    public function joinTo(DbModel $relatedModel, bool $switch = true): DbalInterface
    {
        $relation = $this->getValidatedRelation($relatedModel);

        match ($relation['type']) {
            Relation::HAS_ONE, Relation::HAS_MANY => $this->applyHasRelation($relatedModel, $relation),
            Relation::BELONGS_TO => $this->applyBelongsTo($relatedModel, $relation),
            default => throw ModelException::unsupportedRelationType($relation['type']),
        };

        if ($switch) {
            $this->modelName = $relatedModel::class;
            $this->table = $relatedModel->table;
            $this->idColumn = $relatedModel->idColumn;
            $this->foreignKeys = $relatedModel->relations();
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $relation
     * @throws BaseException
     */
    protected function applyHasRelation(DbModel $relatedModel, array $relation): void
    {
        $this->getOrmModel()->join(
            $relatedModel->table,
            [
                $relatedModel->table . '.' . $relation['foreign_key'],
                '=',
                $this->table . '.' . $relation['local_key'],
            ]
        );
    }

    /**
     * @param array<string, mixed> $relation
     * @throws BaseException
     */
    protected function applyBelongsTo(DbModel $relatedModel, array $relation): void
    {
        $this->getOrmModel()->join(
            $relatedModel->table,
            [
                $relatedModel->table . '.' . $relation['local_key'],
                '=',
                $this->table . '.' . $relation['foreign_key'],
            ]
        );
    }

    /**
     * @throws ModelException
     * @return array<string, mixed>
     */
    private function getValidatedRelation(DbModel $modelToJoin): array
    {
        $relations = $this->getForeignKeys();
        $relatedModelName = $modelToJoin::class;

        if (!isset($relations[$relatedModelName])) {
            throw ModelException::wrongRelation($this->getModelName(), $relatedModelName);
        }

        $relation = $relations[$relatedModelName];

        if (empty($relation['type'])) {
            throw ModelException::relationTypeMissing($this->getModelName(), $relatedModelName);
        }

        if (empty($relation['foreign_key']) || empty($relation['local_key'])) {
            throw ModelException::missingRelationKeys($this->getModelName(), $relatedModelName);
        }

        return $relation;
    }
}
