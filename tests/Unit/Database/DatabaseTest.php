<?php

namespace Quantum\Tests\Unit\Database;

use Quantum\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Database\Database;
use Quantum\Config\Setup;
use Quantum\Di\Di;

class DatabaseTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!config()->has('database')) {
            config()->import(new Setup('config', 'database'));
        }

        config()->set('database.default', 'sqlite');

        config()->set('debug', true);

        Database::execute('CREATE TABLE IF NOT EXISTS profiles (
                        id INTEGER PRIMARY KEY,
                        firstname VARCHAR(255),
                        lastname VARCHAR(255),
                        age INTEGER(11),
                        country VARCHAR(255),
                        created_at DATETIME
                    )');
    }

    public function tearDown(): void
    {
        Database::execute('DROP TABLE IF EXISTS profiles');

        parent::tearDown();
    }

    public function testDatabaseDiReturnsSameInstance(): void
    {
        $db1 = Di::get(Database::class);

        $db2 = Di::get(Database::class);

        $this->assertInstanceOf(Database::class, $db1);

        $this->assertSame($db1, $db2);
    }

    public function testDatabaseGetConfigs(): void
    {
        $this->assertEquals(config()->get('database.sqlite'), Di::get(Database::class)->getConfigs());
    }

    public function testDatabaseGetOrmClass(): void
    {
        $this->assertEquals(IdiormDbal::class, Di::get(Database::class)->getOrmClass());
    }

    public function testDatabaseRawQueries(): void
    {
        $result = Database::query('SELECT * FROM profiles WHERE id=:id', ['id' => 1]);

        $this->assertIsArray($result);

        $this->assertEmpty($result);

        Database::execute(
            'INSERT INTO profiles (firstname, lastname, age, country)
                                     VALUES (:firstname, :lastname, :age, :country)',
            ['firstname' => 'John', 'lastname' => 'Doe', 'age' => '56', 'country' => 'Spain']
        );

        $result = Database::query('SELECT * FROM profiles WHERE id=:id', ['id' => 1]);

        $this->assertNotEmpty($result);

        $this->assertEquals('John', $result[0]['firstname']);

        $this->assertEquals('Doe', $result[0]['lastname']);

        $this->assertEquals("SELECT * FROM profiles WHERE '1'=:'1'", Database::lastQuery());

        $this->assertIsArray(Database::queryLog());
    }

    public function testDatabaseTransactionMethods(): void
    {
        Database::beginTransaction();

        Database::execute('INSERT INTO profiles (firstname, lastname, age, country)
                                 VALUES (:firstname, :lastname, :age, :country)', [
            'firstname' => 'Alice',
            'lastname' => 'Smith',
            'age' => 30,
            'country' => 'Canada',
        ]);

        Database::rollback();

        $result = Database::query('SELECT * FROM profiles WHERE firstname = :firstname', ['firstname' => 'Alice']);

        $this->assertEmpty($result);

        Database::beginTransaction();

        Database::execute('INSERT INTO profiles (firstname, lastname, age, country)
                                 VALUES (:firstname, :lastname, :age, :country)', [
            'firstname' => 'Bob',
            'lastname' => 'Marley',
            'age' => 42,
            'country' => 'Jamaica',
        ]);

        Database::commit();

        $result = Database::query('SELECT * FROM profiles WHERE firstname = :firstname', ['firstname' => 'Bob']);

        $this->assertNotEmpty($result);

        $this->assertEquals('Marley', $result[0]['lastname']);
    }

    public function testDatabaseTransactionClosureSuccess(): void
    {
        $result = Database::transaction(function (): string {
            Database::execute('INSERT INTO profiles (firstname, lastname, age, country)
                                     VALUES (:firstname, :lastname, :age, :country)', [
                'firstname' => 'Charlie',
                'lastname' => 'Brown',
                'age' => 28,
                'country' => 'USA',
            ]);

            return 'committed';
        });

        $this->assertEquals('committed', $result);

        $rows = Database::query('SELECT * FROM profiles WHERE firstname = :firstname', ['firstname' => 'Charlie']);

        $this->assertNotEmpty($rows);

        $this->assertEquals('Brown', $rows[0]['lastname']);
    }

    public function testDatabaseTransactionClosureFailure(): void
    {
        try {
            Database::transaction(function (): void {
                Database::execute('INSERT INTO profiles (firstname, lastname, age, country)
                                         VALUES (:firstname, :lastname, :age, :country)', [
                    'firstname' => 'Eve',
                    'lastname' => 'Hacker',
                    'age' => 999,
                    'country' => 'Matrix',
                ]);

                throw new \Exception('Something went wrong!');
            });
        } catch (\Exception $e) {
            $this->assertEquals('Something went wrong!', $e->getMessage());
        }

        $rows = Database::query('SELECT * FROM profiles WHERE firstname = :firstname', ['firstname' => 'Eve']);

        $this->assertEmpty($rows);
    }
}
