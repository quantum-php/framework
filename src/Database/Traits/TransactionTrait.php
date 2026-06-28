<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Traits;

use Quantum\Database\Exceptions\DatabaseException;
use Quantum\App\Exceptions\BaseException;
use ReflectionException;
use Throwable;

/**
 * Trait TransactionTrait
 * @package Quantum\Database
 */
trait TransactionTrait
{
    /**
     * Begins a transaction
     * @throws BaseException|ReflectionException
     */
    public static function beginTransaction(): void
    {
        self::resolveTransaction(__FUNCTION__);
    }

    /**
     * Commits a transaction
     * @throws BaseException
     * @throws DatabaseException
     */
    public static function commit(): void
    {
        self::resolveTransaction(__FUNCTION__);
    }

    /**
     * Rolls back a transaction
     * @throws BaseException|ReflectionException
     */
    public static function rollback(): void
    {
        self::resolveTransaction(__FUNCTION__);
    }

    /**
     * Resolves the transaction method call
     * @return mixed
     * @throws BaseException|ReflectionException
     */
    protected static function resolveTransaction(string $method)
    {
        $db = db()->getOrmClass();

        if (!method_exists($db, $method)) {
            throw DatabaseException::methodNotSupported($method, self::class);
        }

        return $db::$method();
    }

    /**
     * Transaction wrapper using a closure
     * @return mixed
     * @throws BaseException
     * @throws DatabaseException
     * @throws Throwable
     */
    public static function transaction(callable $callback)
    {
        self::beginTransaction();

        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (Throwable $e) {
            self::rollback();
            throw $e;
        }
    }
}
