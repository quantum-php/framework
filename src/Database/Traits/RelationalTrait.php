<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Traits;

use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Trait RelationalTrait
 * @package Quantum\Database
 */
trait RelationalTrait
{
    /**
     * Raw execute
     * @param array<mixed> $parameters
     * @throws DiException|ReflectionException
     */
    public static function execute(string $query, array $parameters = []): bool
    {
        return self::resolveQuery(__FUNCTION__, $query, $parameters);
    }

    /**
     * Raw query
     * @param array<mixed> $parameters
     * @return array<mixed>
     * @throws DiException|ReflectionException
     */
    public static function query(string $query, array $parameters = []): array
    {
        return self::resolveQuery(__FUNCTION__, $query, $parameters);
    }

    /**
     * Fetches table columns
     * @param array<mixed> $parameters
     * @return array<mixed>
     * @throws DiException|ReflectionException
     */
    public static function fetchColumns(string $query, array $parameters = []): array
    {
        return self::resolveQuery(__FUNCTION__, $query, $parameters);
    }

    /**
     * Gets the last query executed
     * @throws DiException|ReflectionException
     */
    public static function lastQuery(): ?string
    {
        return self::resolveQuery(__FUNCTION__);
    }

    /**
     * Get an array containing all the queries
     * run on a specified connection up to now.
     * @return array<mixed>
     * @throws DiException|ReflectionException
     */
    public static function queryLog(): array
    {
        return self::resolveQuery(__FUNCTION__);
    }

    /**
     * Resolves the requested query
     * @param array<mixed> $parameters
     * @return mixed
     * @throws DiException|ReflectionException
     */
    protected static function resolveQuery(string $method, string $query = '', array $parameters = [])
    {
        return db()->getOrmClass()::$method($query, $parameters);
    }
}
