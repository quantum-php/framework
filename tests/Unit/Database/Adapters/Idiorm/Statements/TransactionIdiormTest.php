<?php

namespace Libraries\Database\Adapters\Idiorm\Statements;

use Quantum\Tests\Unit\Database\Adapters\Idiorm\IdiormDbalTestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Quantum\Database\Adapters\Idiorm\IdiormDbal;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class TransactionIdiormTest extends IdiormDbalTestCase
{
    public function testBeginTransactionAndRollback(): void
    {
        IdiormDbal::beginTransaction();

        IdiormDbal::execute('INSERT INTO profiles (firstname, lastname, age, country)
                                 VALUES (:firstname, :lastname, :age, :country)', [
            'firstname' => 'Alice',
            'lastname' => 'Smith',
            'age' => 30,
            'country' => 'Canada',
        ]);

        IdiormDbal::rollback();

        $result = IdiormDbal::query('SELECT * FROM profiles WHERE firstname = :firstname', ['firstname' => 'Alice']);

        $this->assertEmpty($result);
    }

    public function testBeginTransactionAndCommit(): void
    {
        IdiormDbal::beginTransaction();

        IdiormDbal::execute('INSERT INTO profiles (firstname, lastname, age, country)
                                 VALUES (:firstname, :lastname, :age, :country)', [
            'firstname' => 'Bob',
            'lastname' => 'Marley',
            'age' => 42,
            'country' => 'Jamaica',
        ]);

        IdiormDbal::commit();

        $result = IdiormDbal::query('SELECT * FROM profiles WHERE firstname = :firstname', ['firstname' => 'Bob']);

        $this->assertNotEmpty($result);

        $this->assertEquals('Marley', $result[0]['lastname']);
    }
}
