<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Idiorm\Statements;

use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Database\Contracts\DbalInterface;
use Quantum\App\Exceptions\BaseException;

/**
 * Trait Result
 * @package Quantum\Database
 */
trait Result
{
    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function get(): array
    {
        $results = $this->getOrmModel()->find_many();
        $items = is_array($results) ? $results : iterator_to_array($results);

        return array_map(function ($element): object {
            $item = clone $this;
            $item->updateOrmModel($element);
            return $item;
        }, $items);
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function findOne(int $id): DbalInterface
    {
        $ormObject = $this->getOrmModel()->find_one($id);

        if ($ormObject) {
            $this->updateOrmModel($ormObject);
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function findOneBy(string $column, $value): DbalInterface
    {
        $ormObject = $this->getOrmModel()->where($column, $value)->find_one();
        if ($ormObject) {
            $this->updateOrmModel($ormObject);
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function first(): DbalInterface
    {
        $ormObject = $this->getOrmModel()->find_one();
        if ($ormObject) {
            $this->updateOrmModel($ormObject);
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function count(): int
    {
        return $this->getOrmModel()->count();
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function asArray(): array
    {
        $result = $this->getOrmModel()->as_array();

        if (count($this->hidden) > 0) {
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
}
