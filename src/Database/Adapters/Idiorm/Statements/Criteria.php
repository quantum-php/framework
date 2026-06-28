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
 * Trait Criteria
 * @package Quantum\Database
 */
trait Criteria
{
    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function criteria(string $column, string $operator, $value = null): DbalInterface
    {
        if (!array_key_exists($operator, $this->operators)) {
            throw DatabaseException::operatorNotSupported($operator);
        }

        $this->addCriteria($column, $operator, $value, $this->operators[$operator]);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function criterias(...$criterias): DbalInterface
    {
        foreach ($criterias as $criteria) {
            if (isset($criteria[0]) && is_array($criteria[0])) {
                $this->orCriteria($criteria);
            } else {
                $this->criteria(...$criteria);
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function having(string $column, string $operator, ?string $value = null): DbalInterface
    {
        if (!array_key_exists($operator, $this->operators)) {
            throw DatabaseException::operatorNotSupported($operator);
        }

        $func = $this->operators[$operator];
        $this->getOrmModel()->$func($column, $value);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function isNull(string $column): DbalInterface
    {
        $this->getOrmModel()->where_null($column);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function isNotNull(string $column): DbalInterface
    {
        $this->getOrmModel()->where_not_null($column);
        return $this;
    }

    /**
     * Adds Criteria
     * @param mixed $value
     * @return void
     * @throws DatabaseException|BaseException
     */
    protected function addCriteria(string $column, string $operator, $value, ?string $func = null)
    {
        if ($operator === '#=#') {
            $this->getOrmModel()->where_raw($column . ' = ' . $value);
        } elseif (is_array($value) && count($value) === 1 && key($value) == 'fn') {
            $this->getOrmModel()->where_raw($column . ' ' . $operator . ' ' . $value['fn']);
        } else {
            $this->getOrmModel()->$func($column, $value);
        }
    }

    /**
     * Adds one or more OR criteria in brackets
     * @param array<int, array{0: string, 1: string, 2: mixed}> $orCriterias
     * @return void
     * @throws DatabaseException|BaseException
     */
    protected function orCriteria(array $orCriterias)
    {
        $clause = '';
        $params = [];

        foreach ($orCriterias as $index => $criteria) {
            if ($index == 0) {
                $clause .= '(';
            }

            $clause .= '`' . $criteria[0] . '` ' . $criteria[1] . ' ?';

            if ($index === array_key_last($orCriterias)) {
                $clause .= ')';
            } else {
                $clause .= ' OR ';
            }

            $params[] = $criteria[2];
        }

        $this->getOrmModel()->where_raw($clause, $params);
    }
}
