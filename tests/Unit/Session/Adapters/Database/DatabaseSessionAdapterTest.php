<?php

namespace Quantum\Tests\Unit\Session\Adapters\Database;

use Quantum\Session\Adapters\Database\DatabaseSessionAdapter;
use Quantum\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Tests\Unit\Session\TestCaseHelper;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Tests\Unit\AppTestCase;

class DatabaseSessionAdapterTest extends AppTestCase
{
    use TestCaseHelper;

    private DatabaseSessionAdapter $session;

    public function setUp(): void
    {
        parent::setUp();

        IdiormDbal::connect(['driver' => 'sqlite', 'database' => ':memory:']);

        $this->_createSessionsTable();

        $this->session = new DatabaseSessionAdapter();
    }

    public function tearDown(): void
    {
        session_write_close();

        ModelFactory::createDynamicModel('sessions')->deleteMany();

        parent::tearDown();
    }

    public function testDatabaseSessionConstructor(): void
    {
        $this->assertInstanceOf(DatabaseSessionAdapter::class, $this->session);
    }

    public function testDatabaseSessionAll(): void
    {
        $this->assertEmpty($this->session->all());

        $this->session->set('test', 'Test data');

        $this->session->set('user', ['username' => 'test@unit.com']);

        $this->assertNotEmpty($this->session->all());

        $this->assertIsArray($this->session->all());

        $this->assertArrayHasKey('test', $this->session->all());

        $this->assertEquals('Test data', $this->session->all()['test']);
    }

    public function testDatabaseSessionGetSetHasDelete(): void
    {
        $this->assertNull($this->session->get('auth'));

        $this->assertFalse($this->session->has('auth'));

        $this->session->set('auth', 'Authenticated');

        $this->assertTrue($this->session->has('auth'));

        $this->assertEquals('Authenticated', $this->session->get('auth'));

        $this->session->delete('auth');

        $this->assertFalse($this->session->has('auth'));

        $this->assertNull($this->session->get('auth'));
    }

    public function testDatabaseSessionGetSetFlash(): void
    {
        $this->session->setFlash('message', 'Flash message');

        $this->assertEquals('Flash message', $this->session->getFlash('message'));

        $this->assertNull($this->session->getFlash('message'));
    }

    public function testDatabaseSessionFlush(): void
    {
        $this->session->set('test', 'Test data');

        $this->assertNotEmpty($this->session->all());

        $this->session->flush();

        $this->assertEmpty($this->session->all());
    }

    public function testDatabaseSessionGetSessionId(): void
    {
        $this->assertEquals(session_id(), $this->session->getId());
    }

    public function testDatabaseSessionRegenerateSessionId(): void
    {
        $sessionId = $this->session->getId();

        $this->assertEquals(session_id(), $sessionId);

        $this->session->regenerateId();

        $this->assertNotEquals(session_id(), $sessionId);
    }
}
