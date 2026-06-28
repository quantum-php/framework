<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Factories;

use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Database\Schemas\Table;
use Quantum\Database\Database;
use Exception;

/**
 * Class TableFactory
 * @package Quantum\Database
 */
class TableFactory
{
    /**
     * Creates new table
     * @throws DatabaseException
     */
    public function create(string $name): Table
    {
        if ($this->checkTableExists($name)) {
            throw DatabaseException::tableAlreadyExists($name);
        }

        return $this->createInstance($name)->setAction(Table::CREATE);
    }

    /**
     * Get the table
     * @throws DatabaseException
     */
    public function get(string $name): Table
    {
        if (!$this->checkTableExists($name)) {
            throw DatabaseException::tableDoesNotExists($name);
        }

        return $this->createInstance($name)->setAction(Table::ALTER);
    }

    /**
     * Renames the table
     * @throws DatabaseException
     */
    public function rename(string $oldName, string $newName): bool
    {
        if (!$this->checkTableExists($oldName)) {
            throw DatabaseException::tableDoesNotExists($oldName);
        }

        $this->createInstance($oldName)->setAction(Table::RENAME, ['newName' => $newName]);
        return true;
    }

    /**
     * Drops the table
     * @throws DatabaseException
     */
    public function drop(string $name): bool
    {
        if (!$this->checkTableExists($name)) {
            throw DatabaseException::tableDoesNotExists($name);
        }

        $this->createInstance($name)->setAction(Table::DROP);
        return true;
    }

    /**
     * Checks if the DB table exists
     */
    public function checkTableExists(string $name): bool
    {
        try {
            Database::query('SELECT 1 FROM ' . $name);
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * Creates new Table instance
     */
    private function createInstance(string $name): Table
    {
        return new Table($name);
    }
}
