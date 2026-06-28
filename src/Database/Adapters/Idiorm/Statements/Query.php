<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Idiorm\Statements;

use Quantum\Database\Exceptions\DatabaseException;
use Quantum\App\Exceptions\BaseException;
use PDOStatement;
use PDOException;

/**
 * Trait Query
 * @package Quantum\Database
 */
trait Query
{
    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public static function execute(string $query, array $parameters = []): bool
    {
        if (!self::getConnection()) {
            throw DatabaseException::missingConfig('database');
        }

        try {
            return (self::$ormClass)::raw_execute($query, $parameters);
        } catch (PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                (int) $e->getCode(),
            );
        }
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public static function query(string $query, array $parameters = []): array
    {
        if (!self::getConnection()) {
            throw DatabaseException::missingConfig('database');
        }

        try {
            return (self::$ormClass)::for_table('dummy')
                ->raw_query($query, $parameters)
                ->find_array();
        } catch (PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                (int) $e->getCode(),
            );
        }
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public static function lastQuery(): ?string
    {
        if (!self::getConnection()) {
            throw DatabaseException::missingConfig('database');
        }

        return (self::$ormClass)::get_last_query();
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public static function lastStatement(): object
    {
        if (!self::getConnection()) {
            throw DatabaseException::missingConfig('database');
        }

        return (self::$ormClass)::get_last_statement();
    }

    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public static function queryLog(): array
    {
        if (!self::getConnection()) {
            throw DatabaseException::missingConfig('database');
        }

        return (self::$ormClass)::get_query_log();
    }

    /**
     * Fetches columns of the table
     * @return array<string>
     * @throws DatabaseException|BaseException
     */
    public static function fetchColumns(string $table): array
    {
        $columns = [];

        self::query('SELECT * FROM ' . $table);
        $statement = self::lastStatement();

        if ($statement instanceof PDOStatement) {
            for ($i = 0; $i < $statement->columnCount(); $i++) {
                $meta = $statement->getColumnMeta($i);
                if (is_array($meta)) {
                    $columns[] = $meta['name'];
                }
            }
        }

        return $columns;
    }
}
