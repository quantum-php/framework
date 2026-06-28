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

/**
 * Trait Transaction
 * @package Quantum\Database
 */
trait Transaction
{
    /**
     * Begins a transaction
     * @throws BaseException
     */
    public static function beginTransaction(): void
    {
        if (!self::getConnection()) {
            throw DatabaseException::missingConfig('database');
        }

        (self::$ormClass)::get_db()->beginTransaction();
    }

    /**
     * Commits a transaction
     * @throws BaseException
     */
    public static function commit(): void
    {
        if (!self::getConnection()) {
            throw DatabaseException::missingConfig('database');
        }

        (self::$ormClass)::get_db()->commit();
    }

    /**
     * Rolls back a transaction
     * @throws BaseException
     */
    public static function rollback(): void
    {
        if (!self::getConnection()) {
            throw DatabaseException::missingConfig('database');
        }

        (self::$ormClass)::get_db()->rollBack();
    }
}
