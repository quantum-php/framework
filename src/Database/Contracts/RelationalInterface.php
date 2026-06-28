<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Contracts;

/**
 * Database Abstract Layer interface
 * @package Quantum\Database
 */
interface RelationalInterface
{
    /**
     * Raw execute
     * @param array<mixed> $parameters
     */
    public static function execute(string $query, array $parameters = []): bool;

    /**
     * Raw query
     * @param array<mixed> $parameters
     * @return array<mixed>
     */
    public static function query(string $query, array $parameters = []): array;

    /**
     * Gets the last query executed
     */
    public static function lastQuery(): ?string;

    /**
     * Returns the PDOStatement instance last used
     */
    public static function lastStatement(): object;

    /**
     * Get an array containing all the queries
     * run on a specified connection up to now.
     * @return array<mixed>
     */
    public static function queryLog(): array;

    /**
     * Adds a simple JOIN source to the query
     * @param array<string, mixed> $constraint
     */
    public function join(string $table, array $constraint, ?string $tableAlias = null): DbalInterface;

    /**
     * Adds an INNER JOIN source to the query
     * @param array<string, mixed> $constraint
     */
    public function innerJoin(string $table, array $constraint, ?string $tableAlias = null): DbalInterface;

    /**
     * Adds an LEFT JOIN source to the query
     * @param array<string, mixed> $constraint
     */
    public function leftJoin(string $table, array $constraint, ?string $tableAlias = null): DbalInterface;

    /**
     * Adds an RIGHT JOIN source to the query
     * @param array<string, mixed> $constraint
     */
    public function rightJoin(string $table, array $constraint, ?string $tableAlias = null): DbalInterface;

}
