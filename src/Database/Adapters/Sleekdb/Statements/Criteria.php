<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Sleekdb\Statements;

use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Database\Contracts\DbalInterface;

/**
 * Trait Criteria
 * @package Quantum\Database
 */
trait Criteria
{
    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function criteria(string $column, string $operator, $value = null): DbalInterface
    {
        if (!in_array($operator, $this->operators)) {
            throw DatabaseException::operatorNotSupported($operator);
        }

        $this->criterias[] = [$column, $operator, $this->sanitizeValue($value)];

        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
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
     * @throws DatabaseException
     */
    public function having(string $column, string $operator, ?string $value = null): DbalInterface
    {
        if (!in_array($operator, $this->operators)) {
            throw DatabaseException::operatorNotSupported($operator);
        }

        $this->havings[] = [$column, $operator, $value];

        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function isNull(string $column): DbalInterface
    {
        return $this->criteria($column, '=', null);
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function isNotNull(string $column): DbalInterface
    {
        return $this->criteria($column, '!=', null);
    }

    /**
     * Adds one or more OR criteria in brackets
     * @param array<int, array<string, mixed>> $orCriterias
     * @return void
     * @throws DatabaseException
     */
    protected function orCriteria(array $orCriterias)
    {
        foreach ($orCriterias as $index => $criteria) {
            $this->criteria(...$criteria);

            if ($index !== array_key_last($orCriterias)) {
                $this->criterias[] = 'OR';
            }
        }
    }

    /**
     * @param mixed $value
     * @return mixed|string|null
     */
    protected function sanitizeValue($value)
    {
        $escapeMap = [
            '\\' => '\\\\',
            '.' => '\.',
            '^' => '\^',
            '$' => '\$',
            '*' => '\*',
            '+' => '\+',
            '?' => '\?',
            '(' => '\(',
            ')' => '\)',
            '[' => '\[',
            ']' => '\]',
            '{' => '\{',
            '}' => '\}',
            '|' => '\|',
        ];

        if (is_array($value)) {
            return array_map(fn ($v): mixed => is_string($v) ? strtr($v, $escapeMap) : $v, $value);
        }

        return is_string($value) ? strtr($value, $escapeMap) : $value;
    }
}
