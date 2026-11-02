<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Schemas;

use Quantum\Migration\Exceptions\MigrationException;
use Quantum\Database\Exceptions\DatabaseException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Database\Enums\TableAction;
use Quantum\Database\Traits\TableTrait;
use Quantum\Database\Enums\Type;
use Quantum\Database\Enums\Key;
use Quantum\Database\Database;

/**
 * Class Table
 * @package Quantum\Database
 *
 * @method self autoIncrement()
 * @method self primary()
 * @method self index(string $name = null)
 * @method self unique(string $name = null)
 * @method self fulltext(string $name = null)
 * @method self spatial(string $name = null)
 * @method self nullable(bool $indeed = true)
 * @method self default($value, bool $quoted = true)
 * @method self defaultQuoted()
 * @method self attribute(?string $value)
 * @method self comment(?string $value)
 */
class Table
{
    use TableTrait;

    private string $name;

    private string $newName = '';

    private ?TableAction $action = null;

    /**
     * @var array<int|string, mixed>
     */
    private array $columns = [];

    /**
     * @var array<int|string, mixed>
     */
    private $indexKeys = [];

    /**
     * @var array<int|string, mixed>
     */
    private $droppedIndexKeys = [];

    /**
     * Table constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Table destructor
     * Saves the data before object goes out of scope
     * @throws DatabaseException
     */
    public function __destruct()
    {
        $this->save();
    }

    /**
     * Renames the table
     */
    public function renameTo(string $newName): Table
    {
        $this->newName = $newName;
        return $this;
    }

    /**
     * Sets an action on a table to be performed
     * @param array<string, mixed> $data
     */
    public function setAction(TableAction $action, ?array $data = null): Table
    {
        $this->action = $action;

        if ($data) {
            $key = key($data);
            $this->$key = $data[$key];
        }

        return $this;
    }

    /**
     * Adds column to the table
     * @param mixed $constraint
     */
    public function addColumn(string $name, Type|string $type, $constraint = null): Table
    {
        $this->columns[] = [
            'column' => new Column($name, $type, $constraint),
            'action' => $this->action === TableAction::ALTER ? Column::ADD : null,
        ];

        return $this;
    }

    /**
     * Modifies the column
     * @param mixed $constraint
     */
    public function modifyColumn(string $name, Type|string $type, $constraint = null): Table
    {
        if ($this->action === TableAction::ALTER) {
            $this->columns[] = [
                'column' => new Column($name, $type, $constraint),
                'action' => Column::MODIFY,
            ];
        }

        return $this;
    }

    /**
     * Renames the column name
     */
    public function renameColumn(string $oldName, string $newName): void
    {
        if ($this->action === TableAction::ALTER) {
            $this->columns[] = [
                'column' => (new Column($oldName))->renameTo($newName),
                'action' => Column::RENAME,
            ];
        }
    }

    /**
     * Drops the column
     */
    public function dropColumn(string $name): void
    {
        if ($this->action === TableAction::ALTER) {
            $this->columns[] = [
                'column' => new Column($name),
                'action' => Column::DROP,
            ];
        }
    }

    /**
     * Adds new index to column
     */
    public function addIndex(string $columnName, Key $indexType, ?string $indexName = null): void
    {
        if ($this->action === TableAction::ALTER) {
            $this->columns[] = [
                'column' => new Column($columnName),
                'action' => Column::ADD_INDEX,
            ];

            match ($indexType) {
                Key::PRIMARY => $this->primary(),
                Key::INDEX => $this->index($indexName),
                Key::UNIQUE => $this->unique($indexName),
                Key::FULLTEXT => $this->fulltext($indexName),
                Key::SPATIAL => $this->spatial($indexName),
            };
        }
    }

    /**
     * Drops the column index
     */
    public function dropIndex(string $indexName): void
    {
        if ($this->action === TableAction::ALTER) {
            $this->columns[] = [
                'column' => (new Column('dummy'))->indexDrop($indexName),
                'action' => Column::DROP_INDEX,
            ];
        }
    }

    /**
     * Adds columns after specified one
     */
    public function after(string $columnName): Table
    {
        $this->columns[$this->columnKey()]['column']->after($columnName);
        return $this;
    }

    /**
     * Gets the generated query
     */
    public function getSql(): string
    {
        return match ($this->action) {
            TableAction::CREATE => $this->createTableSql(),
            TableAction::ALTER => $this->alterTableSql(),
            TableAction::RENAME => $this->renameTableSql(),
            TableAction::DROP => $this->dropTableSql(),
            null => '',
        };
    }

    /**
     * Allows to call methods of Column class
     * @param array<mixed>|null $arguments
     * @return $this
     * @throws BaseException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists(Column::class, $method)) {
            throw MigrationException::methodNotSupported($method, Column::class);
        }

        $this->columns[$this->columnKey()]['column']->{$method}(...$arguments);
        return $this;
    }

    /**
     * Saves the query
     * @throws DatabaseException
     */
    private function save(): void
    {
        $sql = $this->getSql();

        if ($sql !== '' && $sql !== '0') {
            Database::execute($sql);
        }
    }

    /**
     * Checks if column exists on a table
     * @throws DatabaseException
     */
    private function checkColumnExists(string $columnName): bool
    {
        $columnIndex = null;

        $columns = Database::fetchColumns($this->name);

        foreach ($columns as $index => $column) {
            if ($columnName == $column) {
                $columnIndex = $index;
                break;
            }
        }

        return !is_null($columnIndex);
    }

    /**
     * Gets the column key
     */
    private function columnKey(): int
    {
        return (int) array_key_last($this->columns);
    }
}
