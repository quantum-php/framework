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
use RecursiveIteratorIterator;
use RecursiveArrayIterator;

/**
 * Trait Modifier
 * @package Quantum\Database
 */
trait Reducer
{
    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function select(...$columns): DbalInterface
    {
        array_walk($columns, function (&$column): void {
            if (is_array($column)) {
                $column = array_flip($column);
            }
        });

        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($columns));
        $columns = iterator_to_array($iterator, true);

        $this->getOrmModel()->select_many($columns);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function groupBy(string $column): DbalInterface
    {
        $this->getOrmModel()->group_by($column);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function orderBy(string $column, string $direction): DbalInterface
    {
        match (strtolower($direction)) {
            'asc' => $this->getOrmModel()->order_by_asc($column),
            'desc' => $this->getOrmModel()->order_by_desc($column),
            default => $this,
        };

        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function offset(int $offset): DbalInterface
    {
        $this->getOrmModel()->offset($offset);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function limit(int $limit): DbalInterface
    {
        $this->getOrmModel()->limit($limit);
        return $this;
    }
}
