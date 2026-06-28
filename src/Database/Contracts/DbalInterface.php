<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Contracts;

use Quantum\Model\DbModel;

/**
 * Database Abstract Layer interface
 * @package Quantum\Database
 */
interface DbalInterface
{
    /**
     * Connects to database
     * @param array<string, mixed> $config
     * @return void
     */
    public static function connect(array $config);

    /**
     * Gets the active connection
     * @return array<string, mixed>|null
     */
    public static function getConnection(): ?array;

    /**
     * Close the database connection
     * @return void
     */
    public static function disconnect();

    /**
     * Gets the table
     */
    public function getTable(): string;

    /**
     * Selects the values from provided table columns
     * @param mixed $columns
     */
    public function select(...$columns): DbalInterface;

    /**
     * Finds the record by primary key
     */
    public function findOne(int $id): DbalInterface;

    /**
     * Finds the record by given column and value
     * @param mixed $value
     */
    public function findOneBy(string $column, $value): DbalInterface;

    /**
     * Gets the first item
     */
    public function first(): DbalInterface;

    /**
     * Adds a criteria to query
     * @param mixed|null $value
     */
    public function criteria(string $column, string $operator, $value = null): DbalInterface;

    /**
     * Adds criteria to query
     * @param array<string, mixed> ...$criterias
     */
    public function criterias(...$criterias): DbalInterface;

    /**
     * Adds having criteria to query
     */
    public function having(string $column, string $operator, ?string $value = null): DbalInterface;

    /**
     * Groups the result by given column
     */
    public function groupBy(string $column): DbalInterface;

    /**
     * Orders the result by ascending or descending
     */
    public function orderBy(string $column, string $direction): DbalInterface;

    /**
     * Returns the result by given offset (works when limit also applied)
     */
    public function offset(int $offset): DbalInterface;

    /**
     * Returns the limited result set
     */
    public function limit(int $limit): DbalInterface;

    /**
     * Gets the result set
     * @return array<mixed>
     */
    public function get(): array;

    /**
     * Returns the count
     */
    public function count(): int;

    /**
     * Returns the data as array
     * @return array<mixed>
     */
    public function asArray(): array;

    /**
     * Sets or gets the model property
     * @param mixed|null $value
     * @return void|mixed|null
     */
    public function prop(string $key, $value = null);

    /**
     * Creates new db record
     */
    public function create(): DbalInterface;

    /**
     * Saves the data into the database
     */
    public function save(): bool;

    /**
     * Deletes the record from the database
     */
    public function delete(): bool;

    /**
     * Deletes all records from the table
     */
    public function truncate(): bool;

    /**
     * Deletes many records by previously applied criteria
     */
    public function deleteMany(): bool;

    /**
     * Joins two models
     */
    public function joinTo(DbModel $model, bool $switch = true): DbalInterface;

    /**
     * Checks if the given column is NULL
     */
    public function isNull(string $column): DbalInterface;

    /**
     * Checks if the given column is NOT NULL
     */
    public function isNotNull(string $column): DbalInterface;
}
