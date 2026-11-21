<?php

namespace Quantum\Tests\Unit\Session\Adapters\Database;

use Quantum\Session\Adapters\Database\DatabaseHandler;
use Quantum\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Tests\Unit\Session\TestCaseHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Quantum\Database\Contracts\DbalInterface;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Tests\Unit\AppTestCase;
use ReflectionMethod;
use Mockery;

class DatabaseHandlerTest extends AppTestCase
{
    use TestCaseHelper;

    public function setUp(): void
    {
        parent::setUp();

        IdiormDbal::connect(['driver' => 'sqlite', 'database' => ':memory:']);

        $this->_createSessionsTable();
    }

    public function tearDown(): void
    {
        ModelFactory::createDynamicModel('sessions')->deleteMany();

        parent::tearDown();
    }

    public function testDatabaseHandlerGcDeletesTheExpiredSessionsAndReturnsTheirNumber(): void
    {
        $this->insertSession('expired-1', time() - 7200);
        $this->insertSession('expired-2', time() - 5400);
        $this->insertSession('active', time());

        $this->assertSame(2, $this->createHandler()->gc(3600));

        $this->assertSame([['session_id' => 'active']], IdiormDbal::query('SELECT session_id FROM sessions'));
    }

    public function testDatabaseHandlerGcReturnsZeroWhenNoSessionHasExpired(): void
    {
        $this->insertSession('active', time());

        $this->assertSame(0, $this->createHandler()->gc(3600));

        $this->assertSame([['session_id' => 'active']], IdiormDbal::query('SELECT session_id FROM sessions'));
    }

    public function testDatabaseHandlerGcReturnsFalseWhenTheSessionsCannotBeDeleted(): void
    {
        $model = Mockery::mock(DbalInterface::class);
        $model->shouldReceive('criteria')->with('ttl', '<', Mockery::type('int'))->andReturnSelf();
        $model->shouldReceive('count')->andReturn(2);
        $model->shouldReceive('deleteMany')->andReturn(false);

        $this->assertFalse((new DatabaseHandler($model))->gc(3600));
    }

    #[DataProvider('interfaceMethodProvider')]
    public function testDatabaseHandlerDeclaresTheReturnTypeOfItsInterfaceMethods(string $method): void
    {
        $this->assertTrue((new ReflectionMethod(DatabaseHandler::class, $method))->hasReturnType());
    }

    /**
     * A handler serves one session, so every check gets a handler of its own
     */
    private function createHandler(): DatabaseHandler
    {
        return new DatabaseHandler(ModelFactory::createDynamicModel('sessions')->getOrmInstance());
    }

    private function insertSession(string $id, int $ttl): void
    {
        IdiormDbal::execute('INSERT INTO sessions (session_id, data, ttl) VALUES (?, ?, ?)', [$id, 'data', $ttl]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function interfaceMethodProvider(): array
    {
        return [
            'open' => ['open'],
            'close' => ['close'],
            'read' => ['read'],
            'write' => ['write'],
            'destroy' => ['destroy'],
            'gc' => ['gc'],
        ];
    }
}
