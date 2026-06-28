<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Quantum\Migration\Exceptions\MigrationException;
use Quantum\Database\Exceptions\DatabaseException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Migration\MigrationManager;
use Quantum\Console\CliCommand;

/**
 * Class MigrationMigrateCommand
 * @package Quantum\Console
 */
class MigrationMigrateCommand extends CliCommand
{
    /**
     * The console command name.
     */
    protected ?string $name = 'migration:migrate';

    /**
     * The console command description.
     */
    protected ?string $description = 'Migrates the migrations';

    /**
     * Command arguments
     * @var array<int, array<int|string, mixed>>
     */
    protected array $args = [
        ['direction', 'optional', '[up] for upgrade, [down] for downgrade'],
    ];

    /**
     * Command options
     * @var array<int, array<int|string, mixed>>
     */
    protected array $options = [
        ['step', 's', 'optional', 'Number of migrations to apply'],
    ];

    /**
     * Executes the command
     * @throws DatabaseException|BaseException
     */
    public function exec(): void
    {

        $direction = $this->getArgument('direction') ?: MigrationManager::UPGRADE;

        if ($direction == 'down' && !$this->confirm('This operation will revert all the database changes, including the data. Continue?')) {
            $this->info('Operation was canceled!');
            return;
        }

        $step = (int) $this->getOption('step') ?: null;

        $migrationManager = new MigrationManager();

        try {
            $migrated = $migrationManager->applyMigrations($direction, $step);
            $this->info($migrated . ' migration' . ($migrated > 1 ? 's were' : ' was') . ' applied');
        } catch (MigrationException $e) {
            $this->info($e->getMessage());
        }
    }
}
