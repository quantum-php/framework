<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Quantum\Migration\Exceptions\MigrationException;
use Quantum\Migration\MigrationManager;
use Quantum\Console\CliCommand;

/**
 * Class MigrationGenerateCommand
 * @package Quantum\Console
 */
class MigrationGenerateCommand extends CliCommand
{
    /**
     * The console command name.
     */
    protected ?string $name = 'migration:generate';

    /**
     * The console command description.
     */
    protected ?string $description = 'Generates new migration file';

    /**
     * Command arguments
     * @var array<int, array<int|string, mixed>>
     */
    protected array $args = [
        ['action', 'required', 'The action to perform. [create] for creating table, [alter] for altering table, [rename] for renaming table, [drop] for dropping table'],
        ['table', 'required', 'The table name'],
    ];

    /**
     * Executes the command
     * @throws MigrationException
     */
    public function exec(): void
    {
        $migrationManager = new MigrationManager();

        $migrationName = $migrationManager->generateMigration($this->getArgument('table'), $this->getArgument('action'));

        $this->info('Migration file ' . $migrationName . ' successfully created');
    }
}
