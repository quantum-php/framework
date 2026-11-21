<?php

namespace Quantum\Tests\Unit\Database\Factories;

use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Database\Factories\TableFactory;
use Quantum\Database\Schemas\Table;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Database\Database;
use Quantum\Config\Setup;
use Mockery;

class TableFactoryTest extends AppTestCase
{
    private TableFactory $tableFactory;
    private $table;

    public function setUp(): void
    {
        parent::setUp();

        if (!config()->has('database')) {
            config()->import(new Setup('config', 'database', true));
        }

        config()->set('database.default', 'sqlite');

        config()->set('debug', true);

        $this->tableFactory = new TableFactory();

        Mockery::getConfiguration()->setConstantsMap([
            \Quantum\Database\Schemas\Table::class => [
                'CREATE' => 1,
                'ALTER' => 2,
                'DROP' => 3,
                'RENAME' => 4,
            ],
        ]);

        $this->table = Mockery::mock('overload:Quantum\Database\Schemas\Table');

        $this->table->shouldReceive('setAction')->andReturnUsing(fn ($action) => $this->table);

        $this->table->shouldReceive('getAction')->andReturnUsing(fn () => $this->table->action);

        $this->table->shouldReceive('save')->andReturn(0);
    }

    public function tearDown(): void
    {
        Database::execute('DROP TABLE IF EXISTS profiles');

        parent::tearDown();
    }

    public function testCreateTable(): void
    {
        $table = $this->tableFactory->create('profiles');

        $this->assertInstanceOf(Table::class, $table);
    }

    public function testAttemptToCreateTableWhichAlreadyExists(): void
    {
        Database::execute('CREATE TABLE profiles (
                        id INTEGER PRIMARY KEY,
                        firstname VARCHAR(255),
                        lastname VARCHAR(255),
                        age INTEGER(11),
                        country VARCHAR(255),
                        created_at DATETIME
                    )');

        $this->expectException(DatabaseException::class);

        $this->expectExceptionMessage('The table `profiles` is already exists');

        $this->tableFactory->create('profiles');
    }

    public function testGetTable(): void
    {
        Database::execute('CREATE TABLE IF NOT EXISTS profiles (
                        id INTEGER PRIMARY KEY,
                        firstname VARCHAR(255),
                        lastname VARCHAR(255),
                        age INTEGER(11),
                        country VARCHAR(255),
                        created_at DATETIME
                    )');

        $table = $this->tableFactory->get('profiles');

        $this->assertInstanceOf(Table::class, $table);
    }

    public function testAttemptToGetNonExistingTable(): void
    {
        $this->expectException(DatabaseException::class);

        $this->expectExceptionMessage('The table `profiles` does not exists');

        $table = $this->tableFactory->get('profiles');
    }

    public function testRenameTable(): void
    {
        Database::execute('CREATE TABLE IF NOT EXISTS profiles (
                        id INTEGER PRIMARY KEY,
                        firstname VARCHAR(255),
                        lastname VARCHAR(255),
                        age INTEGER(11),
                        country VARCHAR(255),
                        created_at DATETIME
                    )');

        $this->assertTrue($this->tableFactory->rename('profiles', 'system_users'));
    }

    public function testAttemptToRenameNonExistingTable(): void
    {
        $this->expectException(DatabaseException::class);

        $this->expectExceptionMessage('The table `profiles` does not exists');

        $this->tableFactory->rename('profiles', 'system_users');
    }

    public function testDropTable(): void
    {
        Database::execute('CREATE TABLE IF NOT EXISTS profiles (
                        id INTEGER PRIMARY KEY,
                        firstname VARCHAR(255),
                        lastname VARCHAR(255),
                        age INTEGER(11),
                        country VARCHAR(255),
                        created_at DATETIME
                    )');

        $this->assertTrue($this->tableFactory->drop('profiles'));
    }

    public function testAttemptToDropNonExistingTable(): void
    {
        $this->expectException(DatabaseException::class);

        $this->expectExceptionMessage('The table `profiles` does not exists');

        $this->tableFactory->drop('profiles');
    }
}
