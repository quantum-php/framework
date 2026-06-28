<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Migration;

use Quantum\Database\Factories\TableFactory;

/**
 * Class Migration
 * @package Quantum\Migration
 */
abstract class Migration
{
    /**
     * Upgrades with the specified migration class
     */
    abstract public function up(TableFactory $tableFactory): void;

    /**
     * Downgrades with the specified migration class
     */
    abstract public function down(TableFactory $tableFactory): void;
}
