<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Schemas;

use Quantum\Database\Enums\Key;

/**
 * Class Column
 * @package Quantum\Database
 */
class Column
{
    /**
     * Action add
     */
    public const ADD = 'ADD';

    /**
     * Action modify
     */
    public const MODIFY = 'MODIFY';

    /**
     * Action rename
     */
    public const RENAME = 'RENAME';

    /**
     * Action drop
     */
    public const DROP = 'DROP';

    /**
     * Action add index
     */
    public const ADD_INDEX = 'ADD_INDEX';

    /**
     * Action drop index
     */
    public const DROP_INDEX = 'DROP_INDEX';

    /**
     * Name property
     */
    public const NAME = 'name';

    /**
     * New Name property
     */
    public const NEW_NAME = 'newName';

    /**
     * Type property
     */
    public const TYPE = 'type';

    /**
     * Constraint property
     */
    public const CONSTRAINT = 'constraint';

    /**
     * Attribute property
     */
    public const ATTRIBUTE = 'attribute';

    /**
     * Nullable property
     */
    public const NULLABLE = 'nullable';

    /**
     * Default property
     */
    public const DEFAULT = 'default';

    /**
     * After column property
     */
    public const AFTER = 'afterColumn';

    /**
     * Comment property
     */
    public const COMMENT = 'comment';

    /**
     * Auto increment property
     */
    public const AUTO_INCREMENT = 'autoincrement';

    /**
     * Attribute binary
     */
    public const ATTR_BINARY = 'BINARY';

    /**
     * Attribute unsigned
     */
    public const ATTR_UNSIGNED = 'UNSIGNED';

    /**
     * Attribute zero fill
     */
    public const ATTR_ZEROFILL = 'UNSIGNED ZEROFILL';

    private string $name;

    private ?string $newName = null;

    private ?string $type = null;

    /**
     * @var mixed
     */
    private $constraint;

    private ?string $attribute = null;

    private string $nullable = 'NOT NULL';

    /**
     * @var mixed
     */
    private $default;

    private bool $defaultQuoted = true;

    private ?string $autoincrement = null;

    private ?string $indexKey = null;

    private ?string $indexName = null;

    private ?string $indexDrop = null;

    private ?string $comment = null;

    private ?string $afterColumn = null;

    /**
     * Column constructor.
     * @param mixed $constraint
     */
    public function __construct(string $name, ?string $type = null, $constraint = null)
    {
        $this->name = $name;

        if ($type) {
            $this->type = strtoupper($type);
            $this->constraint = $constraint;
        }
    }

    /**
     * Renames the column
     */
    public function renameTo(string $newName): Column
    {
        $this->newName = $newName;
        return $this;
    }

    public function indexDrop(string $indexName): Column
    {
        $this->indexDrop = $indexName;
        return $this;
    }

    /**
     * Gets the column property
     * @return mixed
     */
    public function get(string $property, ?string $action = null)
    {
        return (isset($this->$property) && $action != self::RENAME && $action != self::DROP) ? $this->$property : null;
    }

    /**
     * Makes the column auto incremental
     */
    public function autoIncrement(): void
    {
        $this->autoincrement = 'AUTO_INCREMENT';
        $this->primary();
    }

    /**
     * Adds a primary key the column
     */
    public function primary(): void
    {
        $this->indexKey = Key::PRIMARY;
    }

    /**
     * Adds an index key to the column
     */
    public function index(?string $name = null): void
    {
        $this->indexKey = Key::INDEX;

        if ($name) {
            $this->indexName = $name;
        }
    }

    /**
     * Adds unique key to the column
     */
    public function unique(?string $name = null): void
    {
        $this->indexKey = Key::UNIQUE;

        if ($name) {
            $this->indexName = $name;
        }
    }

    /**
     * Adds a fulltext key the column
     */
    public function fulltext(?string $name = null): void
    {
        $this->indexKey = Key::FULLTEXT;

        if ($name) {
            $this->indexName = $name;
        }
    }

    /**
     * Adds a spatial key the column
     */
    public function spatial(?string $name = null): void
    {
        $this->indexKey = Key::SPATIAL;

        if ($name) {
            $this->indexName = $name;
        }
    }

    /**
     * Adds or removes nullable property
     */
    public function nullable(bool $indeed = true): void
    {
        $this->nullable = ($indeed ? '' : 'NOT ') . 'NULL';
    }

    /**
     * Adds default value to the column
     * @param mixed $value
     */
    public function default($value, bool $quoted = true): void
    {
        $this->default = $value;
        $this->defaultQuoted = $quoted;
    }

    /**
     * Adds or removes attribute to the column
     */
    public function attribute(?string $value): void
    {
        $this->attribute = $value ? strtoupper($value) : null;
    }

    /**
     * Adds or removes comment to the column
     */
    public function comment(?string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * Adds column after a given column
     */
    public function after(string $columnName): void
    {
        $this->afterColumn = $columnName;
    }

    /**
     * Adds quotes on default value
     */
    public function defaultQuoted(): bool
    {
        return $this->defaultQuoted;
    }
}
