<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Migration;

use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Database\Factories\TableFactory;
use Quantum\Database\Enums\Type;

/**
 * Class MigrationTable
 * @package Quantum\Migration
 */
class MigrationTable extends Migration
{
    /**
     * Migrations table name
     */
    public const TABLE = 'migrations';

    /**
     * Creates the migrations table
     * @throws DatabaseException
     */
    public function up(TableFactory $tableFactory): void
    {
        $table = $tableFactory->create(self::TABLE);
        $table->addColumn('id', Type::INT, 11)->autoIncrement();
        $table->addColumn('migration', Type::VARCHAR, 255);
        $table->addColumn('applied_at', Type::TIMESTAMP)->default('CURRENT_TIMESTAMP', false);
    }

    /**
     * Drops the migrations table
     * @throws DatabaseException
     */
    public function down(TableFactory $tableFactory): void
    {
        $tableFactory->drop(self::TABLE);
    }
}
