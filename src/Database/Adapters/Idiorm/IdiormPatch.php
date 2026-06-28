<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Idiorm;

use RuntimeException;
use ORM;

/**
 * Class IdiormPatch
 * @package Quantum\Database
 */
class IdiormPatch extends ORM
{
    private ?ORM $ormModel = null;

    private static ?IdiormPatch $instance = null;

    /**
     * Get Instance
     */
    public static function getInstance(): IdiormPatch
    {
        if (self::$instance == null) {
            self::$instance = new self('dummy');
        }

        return self::$instance;
    }

    /**
     * Set ORM Object
     */
    public function use(ORM $ormModel): IdiormPatch
    {
        $this->ormModel = $ormModel;
        return $this;
    }

    /**
     * Add an LEFT JOIN source to the query
     * @param array<string, mixed> $constraint
     */
    public function leftJoin(string $table, array $constraint, ?string $table_alias = null): object
    {
        return $this->addJoin('LEFT', $table, $constraint, $table_alias);
    }

    /**
     * Add an RIGHT JOIN source to the query
     * @param array<string, mixed> $constraint
     */
    public function rightJoin(string $table, array $constraint, ?string $table_alias = null): object
    {
        return $this->addJoin('RIGHT', $table, $constraint, $table_alias);
    }

    /**
     * Add Join
     * @param array<string, mixed> $constraint
     */
    public function addJoin(string $operator, string $table, array $constraint, ?string $table_alias = null): object
    {
        if ($this->ormModel === null) {
            throw new RuntimeException('ORM model is not set. Call use() method first.');
        }
        return $this->ormModel->_add_join_source($operator, $table, $constraint, $table_alias);
    }
}
