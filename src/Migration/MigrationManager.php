<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Migration;

use Quantum\Migration\Exceptions\MigrationException;
use Quantum\Storage\Exceptions\FileSystemException;
use Quantum\Migration\Templates\MigrationTemplate;
use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Database\Factories\TableFactory;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Storage\FileSystem;
use Quantum\Database\Database;
use ReflectionException;

/**
 * Class MigrationManager
 * @package Quantum\Migration
 */
class MigrationManager
{
    /**
     * Migration direction for upgrade
     */
    public const UPGRADE = 'up';

    /**
     * Migration direction for downgrade
     */
    public const DOWNGRADE = 'down';

    /**
     * Available actions
     */
    public const ACTIONS = ['create', 'alter', 'rename', 'drop'];

    /**
     * Supported drivers
     */
    public const DRIVERS = ['mysql', 'pgsql', 'sqlite'];

    /**
     * Migrations
     * @var array<string, string>
     */
    private array $migrations = [];

    private TableFactory $tableFactory;

    private string $migrationFolder;

    private FileSystem $fs;

    private Database $db;

    /**
     * @throws BaseException|ConfigException|DiException|FileSystemException|ReflectionException
     */
    public function __construct()
    {
        $this->fs = FileSystemFactory::get();

        $this->db = db();

        $this->tableFactory = new TableFactory();

        $this->migrationFolder = base_dir() . DS . 'migrations';

        if (!$this->fs->isDirectory($this->migrationFolder)) {
            throw FileSystemException::directoryNotExists($this->migrationFolder);
        }
    }

    /**
     * Generates new migration file
     * @throws MigrationException
     */
    public function generateMigration(string $table, string $action): string
    {
        if (!in_array($action, self::ACTIONS)) {
            throw MigrationException::unsupportedAction($action);
        }

        $migrationName = $action . '_table_' . strtolower($table) . '_' . time();

        $migrationTemplate = MigrationTemplate::{$action}($migrationName, strtolower($table));

        $this->fs->put($this->migrationFolder . DS . $migrationName . '.php', $migrationTemplate);

        return $migrationName;
    }

    /**
     * Applies migrations
     * @throws MigrationException|DatabaseException|BaseException|DiException|ReflectionException
     */
    public function applyMigrations(string $direction, ?int $step = null): ?int
    {
        $configs = $this->db->getConfigs();
        $databaseDriver = $configs['driver'] ?? '';

        if (!in_array($databaseDriver, self::DRIVERS)) {
            throw MigrationException::driverNotSupported($databaseDriver);
        }

        $migrated = match ($direction) {
            self::UPGRADE => $this->upgrade(),
            self::DOWNGRADE => $this->downgrade($step),
            default => throw MigrationException::wrongDirection(),
        };

        return $migrated;
    }

    /**
     * Runs up migrations
     * @throws MigrationException|DatabaseException|DiException|ReflectionException
     */
    private function upgrade(): int
    {
        if (!$this->tableFactory->checkTableExists(MigrationTable::TABLE)) {
            $migrationTable = new MigrationTable();
            $migrationTable->up($this->tableFactory);
        }

        $this->prepareUpMigrations();

        if (empty($this->migrations)) {
            throw MigrationException::nothingToMigrate();
        }

        $migratedEntries = [];
        foreach ($this->migrations as $migrationFile) {
            $this->fs->require($migrationFile, true);

            $migrationClassName = pathinfo($migrationFile, PATHINFO_FILENAME);

            $migration = new $migrationClassName();

            if (!$migration instanceof Migration) {
                throw MigrationException::invalidMigrationClass($migrationClassName);
            }

            $migration->up($this->tableFactory);

            $migratedEntries[] = $migrationClassName;
        }
        $this->addMigratedEntries($migratedEntries);
        return count($migratedEntries);
    }

    /**
     * Runs down migrations
     * @throws MigrationException|DatabaseException|DiException|ReflectionException
     */
    private function downgrade(?int $step): int
    {
        if (!$this->tableFactory->checkTableExists(MigrationTable::TABLE)) {
            throw DatabaseException::tableDoesNotExists(MigrationTable::TABLE);
        }

        $this->prepareDownMigrations($step);

        if (empty($this->migrations)) {
            throw MigrationException::nothingToMigrate();
        }

        $migratedEntries = [];

        foreach ($this->migrations as $migrationFile) {
            $this->fs->require($migrationFile, true);

            $migrationClassName = pathinfo($migrationFile, PATHINFO_FILENAME);

            $migration = new $migrationClassName();

            if (!$migration instanceof Migration) {
                throw MigrationException::invalidMigrationClass($migrationClassName);
            }

            $migration->down($this->tableFactory);

            $migratedEntries[] = $migrationClassName;
        }

        $this->removeMigratedEntries($migratedEntries);

        return count($migratedEntries);
    }

    /**
     * Prepares up migrations
     * @throws MigrationException|DiException|ReflectionException
     */
    private function prepareUpMigrations(): void
    {
        $migratedEntries = $this->getMigratedEntries();
        $migrationFiles = $this->getMigrationFiles();

        if ($migratedEntries === [] && $migrationFiles === []) {
            throw MigrationException::nothingToMigrate();
        }

        foreach ($migrationFiles as $timestamp => $migrationFile) {
            foreach ($migratedEntries as $migratedEntry) {
                if (pathinfo($migrationFile, PATHINFO_FILENAME) == $migratedEntry['migration']) {
                    continue 2;
                }
            }

            $this->migrations[$timestamp] = $migrationFile;
        }
        ksort($this->migrations);
    }

    /**
     * Prepares down migrations
     * @throws MigrationException|DiException|ReflectionException
     */
    private function prepareDownMigrations(?int $step = null): void
    {
        $migratedEntries = $this->getMigratedEntries();

        if ($migratedEntries === []) {
            throw MigrationException::nothingToMigrate();
        }

        foreach ($migratedEntries as $migratedEntry) {
            $exploded = explode('_', $migratedEntry['migration']);
            $this->migrations[array_pop($exploded)] = $this->migrationFolder . DS . $migratedEntry['migration'] . '.php';
        }

        if (!is_null($step)) {
            $this->migrations = array_slice($this->migrations, count($this->migrations) - $step, $step, true);
        }

        krsort($this->migrations);
    }

    /**
     * Gets migration files
     * @return array<string, string>
     */
    private function getMigrationFiles(): array
    {
        $migrationsFiles = $this->fs->glob($this->migrationFolder . DS . '*.php');

        $migrations = [];

        if (!empty($migrationsFiles)) {
            foreach ($migrationsFiles as $migration) {
                $exploded = explode('_', pathinfo($migration, PATHINFO_FILENAME));
                $migrations[array_pop($exploded)] = $migration;
            }
        }

        return $migrations;
    }

    /**
     * Gets migrated entries from migrations table
     * @return array<string, mixed>
     * @throws DiException|ReflectionException
     */
    private function getMigratedEntries(): array
    {
        return Database::query('SELECT * FROM ' . MigrationTable::TABLE);
    }

    /**
     * Adds migrated entries to migrations table
     * @param array<string> $entries
     * @throws DiException|ReflectionException
     */
    private function addMigratedEntries(array $entries): void
    {
        foreach ($entries as $entry) {
            Database::execute('INSERT INTO ' . MigrationTable::TABLE . '(migration) VALUES(:migration)', ['migration' => $entry]);
        }
    }

    /**
     * Removes migrated entries from migrations table
     * @param array<string> $entries
     * @throws DiException|ReflectionException
     */
    private function removeMigratedEntries(array $entries): void
    {
        foreach ($entries as $entry) {
            Database::execute('DELETE FROM ' . MigrationTable::TABLE . ' WHERE migration=:migration', ['migration' => $entry]);
        }
    }
}
