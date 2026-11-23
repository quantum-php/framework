<?php

namespace Quantum\Tests\Unit\Migration;

use Quantum\Migration\Exceptions\MigrationException;
use Quantum\Storage\Exceptions\FileSystemException;
use Quantum\Migration\MigrationManager;
use Quantum\Migration\MigrationTable;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Database\Database;
use Quantum\Config\Setup;
use Quantum\Di\Di;
use Mockery;

class MigrationManagerTest extends AppTestCase
{
    private string $migrationDir;
    /** @var array<int, string> */
    private array $existingMigrationFiles = [];

    public function setUp(): void
    {
        parent::setUp();

        if (!config()->has('database')) {
            config()->import(new Setup('config', 'database'));
        }

        config()->set('database.default', 'sqlite');

        $this->migrationDir = base_dir() . DS . 'migrations';
        if (!is_dir($this->migrationDir)) {
            mkdir($this->migrationDir, 0777, true);
        }

        $files = glob($this->migrationDir . DS . '*.php');
        $this->existingMigrationFiles = is_array($files) ? $files : [];
    }

    public function tearDown(): void
    {
        $files = glob($this->migrationDir . DS . '*.php');
        $currentFiles = is_array($files) ? $files : [];
        $createdByTest = array_diff($currentFiles, $this->existingMigrationFiles);

        foreach ($createdByTest as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        Database::execute('DROP TABLE IF EXISTS alpha_table');
        Database::execute('DROP TABLE IF EXISTS beta_table');
        Database::execute('DROP TABLE IF EXISTS ' . MigrationTable::TABLE);

        parent::tearDown();
    }

    public function testConstructorThrowsWhenMigrationDirectoryIsMissing(): void
    {
        if (is_dir($this->migrationDir)) {
            @rmdir($this->migrationDir);
        }

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('The directory ' . $this->migrationDir . ' does not exists.');

        try {
            new MigrationManager();
        } finally {
            if (!is_dir($this->migrationDir)) {
                mkdir($this->migrationDir, 0777, true);
            }
        }
    }

    public function testGenerateMigrationThrowsForUnsupportedAction(): void
    {
        $manager = new MigrationManager();

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('The action `sync`, is not supported');

        $manager->generateMigration('users', 'sync');
    }

    public function testGenerateMigrationCreatesFileAndReturnsMigrationName(): void
    {
        $manager = new MigrationManager();

        $migrationName = $manager->generateMigration('Users', 'create');

        $this->assertMatchesRegularExpression('/^create_table_users_\d+$/', $migrationName);
        $this->assertFileExists($this->migrationDir . DS . $migrationName . '.php');
    }

    public function testApplyMigrationsThrowsForUnsupportedDriver(): void
    {
        $ormClass = db()->getOrmClass();

        $this->createContext();

        $db = Mockery::mock(Database::class);
        $db->shouldReceive('getOrmClass')->andReturn($ormClass);
        $db->shouldReceive('getConfigs')->andReturn(['driver' => 'sqlserver']);
        Di::set(Database::class, $db);

        $manager = new MigrationManager();

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('The driver `sqlserver` is not supported.');

        $manager->applyMigrations(MigrationManager::UPGRADE);
    }

    public function testApplyMigrationsThrowsForWrongDirection(): void
    {
        $ormClass = db()->getOrmClass();

        $this->createContext();

        $db = Mockery::mock(Database::class);
        $db->shouldReceive('getOrmClass')->andReturn($ormClass);
        $db->shouldReceive('getConfigs')->andReturn(['driver' => 'sqlite']);
        Di::set(Database::class, $db);

        $manager = new MigrationManager();

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Migration direction can only be [up] or [down]');

        $manager->applyMigrations('sideways');
    }

    public function testApplyMigrationsUpgradeRunsPendingMigrationsInAscendingOrder(): void
    {
        $this->createMigrationsTable();
        $this->createValidMigrationFile('create_table_alpha_table_1001', 'alpha_table');
        $this->createValidMigrationFile('create_table_beta_table_1002', 'beta_table');

        $manager = new MigrationManager();
        $migrated = $manager->applyMigrations(MigrationManager::UPGRADE);

        $this->assertSame(2, $migrated);
        $this->assertTrue($this->tableExists('alpha_table'));
        $this->assertTrue($this->tableExists('beta_table'));

        $entries = Database::query('SELECT migration FROM ' . MigrationTable::TABLE . ' ORDER BY migration ASC');
        $this->assertCount(2, $entries);
        $this->assertSame('create_table_alpha_table_1001', $entries[0]['migration']);
        $this->assertSame('create_table_beta_table_1002', $entries[1]['migration']);
    }

    public function testApplyMigrationsDowngradeRespectsStepAndOrder(): void
    {
        $this->createMigrationsTable();
        $this->createValidMigrationFile('create_table_alpha_table_2001', 'alpha_table');
        $this->createValidMigrationFile('create_table_beta_table_2002', 'beta_table');

        $manager = new MigrationManager();
        $manager->applyMigrations(MigrationManager::UPGRADE);

        $migrated = $manager->applyMigrations(MigrationManager::DOWNGRADE, 1);
        $this->assertSame(1, $migrated);

        $this->assertTrue($this->tableExists('alpha_table'));
        $this->assertFalse($this->tableExists('beta_table'));

        $entries = Database::query('SELECT migration FROM ' . MigrationTable::TABLE . ' ORDER BY migration ASC');
        $this->assertCount(1, $entries);
        $this->assertSame('create_table_alpha_table_2001', $entries[0]['migration']);
    }

    public function testApplyMigrationsUpgradeThrowsWhenNothingToMigrate(): void
    {
        $this->createMigrationsTable();
        $manager = new MigrationManager();

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Nothing to migrate');

        $manager->applyMigrations(MigrationManager::UPGRADE);
    }

    public function testApplyMigrationsUpgradeThrowsForInvalidMigrationClass(): void
    {
        $this->createMigrationsTable();
        $this->createInvalidMigrationFile('create_table_gamma_table_1003');

        $manager = new MigrationManager();

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Migration class `create_table_gamma_table_1003` must extend Migration');

        $manager->applyMigrations(MigrationManager::UPGRADE);
    }

    public function testApplyMigrationsDowngradeThrowsWhenMigrationTableMissing(): void
    {
        Database::execute('DROP TABLE IF EXISTS ' . MigrationTable::TABLE);

        $manager = new MigrationManager();

        $this->expectException(\Quantum\Database\Exceptions\DatabaseException::class);
        $this->expectExceptionMessage('The table `migrations` does not exists');

        $manager->applyMigrations(MigrationManager::DOWNGRADE);
    }

    public function testApplyMigrationsDowngradeThrowsWhenNoEntriesExist(): void
    {
        $this->createMigrationsTable();

        $manager = new MigrationManager();

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Nothing to migrate');

        $manager->applyMigrations(MigrationManager::DOWNGRADE);
    }

    private function createValidMigrationFile(string $className, string $tableName): void
    {
        $content = "<?php\n"
            . 'class ' . $className . " extends \\Quantum\\Migration\\Migration\n"
            . "{\n"
            . "    public function up(\\Quantum\\Database\\Factories\\TableFactory \$tableFactory): void\n"
            . "    {\n"
            . "        \\Quantum\\Database\\Database::execute('CREATE TABLE IF NOT EXISTS " . $tableName . " (id INTEGER PRIMARY KEY, name VARCHAR(255))');\n"
            . "    }\n\n"
            . "    public function down(\\Quantum\\Database\\Factories\\TableFactory \$tableFactory): void\n"
            . "    {\n"
            . "        \\Quantum\\Database\\Database::execute('DROP TABLE IF EXISTS " . $tableName . "');\n"
            . "    }\n"
            . "}\n";

        file_put_contents($this->migrationDir . DS . $className . '.php', $content);
    }

    private function createInvalidMigrationFile(string $className): void
    {
        $content = "<?php\n"
            . 'class ' . $className . "\n"
            . "{\n"
            . "}\n";

        file_put_contents($this->migrationDir . DS . $className . '.php', $content);
    }

    private function tableExists(string $table): bool
    {
        try {
            Database::query('SELECT 1 FROM ' . $table . ' LIMIT 1');
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    private function createMigrationsTable(): void
    {
        Database::execute('CREATE TABLE IF NOT EXISTS ' . MigrationTable::TABLE . ' (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255),
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
    }
}
