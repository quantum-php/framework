<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Migration\Templates;

/**
 * Class MigrationTable
 * @package Quantum\Migration
 */
class MigrationTemplate
{
    /**
     * Create migration template
     */
    public static function create(string $className, string $tableName): string
    {
        return '<?php

use Quantum\Database\Factories\TableFactory;
use Quantum\Migration\Migration;


class ' . ucfirst($className) . ' extends Migration
{
    
    public function up(?TableFactory $tableFactory) {
        $table = $tableFactory->create(\'' . $tableName . '\');
    }
    
    public function down(?TableFactory $tableFactory)
    {
        $tableFactory->drop(\'' . $tableName . '\');
    }

}
       
        ';
    }

    /**
     * Alter migration template
     */
    public static function alter(string $className, string $tableName): string
    {
        return '<?php

use Quantum\Database\Factories\TableFactory;
use Quantum\Migration\Migration;

class ' . ucfirst($className) . ' extends Migration
{
    
    public function up(?TableFactory $tableFactory) {
        $table = $tableFactory->get(\'' . $tableName . '\');
    }
    
    public function down(?TableFactory $tableFactory)
    {
        $table = $tableFactory->get(\'' . $tableName . '\');
    }

}
       
        ';
    }

    /**
     * Rename migration template
     */
    public static function rename(string $className, string $tableName): string
    {
        return '<?php

use Quantum\Database\Factories\TableFactory;
use Quantum\Migration\Migration;

class ' . ucfirst($className) . ' extends Migration
{
    
    public function up(?TableFactory $tableFactory) {
        $tableFactory->rename(\'' . $tableName . '\', $newName);
    }
    
    public function down(?TableFactory $tableFactory)
    {
        $tableFactory->rename($newName, \'' . $tableName . '\');
    }

}
       
        ';
    }

    /**
     * Drop migration template
     */
    public static function drop(string $className, string $tableName): string
    {
        return '<?php

use Quantum\Database\Factories\TableFactory;
use Quantum\Migration\Migration;

class ' . ucfirst($className) . ' extends Migration
{
    
    public function up(?TableFactory $tableFactory) {
        $tableFactory->drop(\'' . $tableName . '\');
    }
    
    public function down(?TableFactory $tableFactory)
    {
        //
    }

}
       
        ';
    }
}
