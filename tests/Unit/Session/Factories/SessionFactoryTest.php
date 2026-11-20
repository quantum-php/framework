<?php

namespace Quantum\Tests\Unit\Session\Factories;

use Quantum\Session\Adapters\Database\DatabaseSessionAdapter;
use Quantum\Session\Adapters\Native\NativeSessionAdapter;
use Quantum\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Session\Exceptions\SessionException;
use Quantum\Tests\Unit\Session\TestCaseHelper;
use Quantum\Session\Factories\SessionFactory;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Session\Enums\SessionType;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Session\Session;
use Quantum\Config\Setup;
use Quantum\Di\Di;

class SessionFactoryTest extends AppTestCase
{
    use TestCaseHelper;

    public function setUp(): void
    {
        parent::setUp();

        $this->resetSessionFactory();

        IdiormDbal::connect(['driver' => 'sqlite', 'database' => ':memory:']);

        $this->_createSessionsTable();

        if (!config()->has('session')) {
            config()->import(new Setup('config', 'session'));
        }
    }

    public function tearDown(): void
    {
        session_write_close();

        ModelFactory::createDynamicModel('sessions')->deleteMany();

        parent::tearDown();
    }

    public function testSessionFactoryInstance(): void
    {
        $session = SessionFactory::get();

        $this->assertInstanceOf(Session::class, $session);
    }

    public function testSessionFactoryGetDefaultSessionAdapter(): void
    {
        $session = SessionFactory::get();

        $this->assertInstanceOf(NativeSessionAdapter::class, $session->getAdapter());
    }

    public function testSessionFactoryGetNativeSessionAdapter(): void
    {
        $session = SessionFactory::get(SessionType::NATIVE);

        $this->assertInstanceOf(NativeSessionAdapter::class, $session->getAdapter());
    }

    public function testSessionFactoryGetDatabaseAdapter(): void
    {
        $session = SessionFactory::get(SessionType::DATABASE);

        $this->assertInstanceOf(DatabaseSessionAdapter::class, $session->getAdapter());
    }

    public function testSessionSubsequentRequests(): void
    {
        config()->set('session.default', 'database');

        $session = SessionFactory::get();

        $this->assertEmpty($session->all());

        $session->set('data', 'Data saved in persistent storage');

        $this->assertNotEmpty($session->all());

        $this->assertIsArray($session->all());

        $this->assertEquals('Data saved in persistent storage', $session->get('data'));

        session_write_close();

        unset($session);

        $this->assertEquals(PHP_SESSION_NONE, session_status());

        @session_start();

        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());

        $session = SessionFactory::get();

        $this->assertNotEmpty($session->all());

        $this->assertIsArray($session->all());

        $this->assertEquals('Data saved in persistent storage', $session->get('data'));
    }

    public function testSessionFactoryInvalidTypeAdapter(): void
    {
        $this->expectException(SessionException::class);

        $this->expectExceptionMessage('The adapter `invalid_type` is not supported');

        SessionFactory::get('invalid_type');
    }

    public function testSessionFactoryReturnsSameInstance(): void
    {
        $session1 = SessionFactory::get(SessionType::NATIVE);
        $session2 = SessionFactory::get(SessionType::NATIVE);

        $this->assertSame($session1, $session2);
    }

    private function resetSessionFactory(): void
    {
        if (!Di::isRegistered(SessionFactory::class)) {
            Di::register(SessionFactory::class);
        }

        $factory = Di::get(SessionFactory::class);
        $this->setPrivateProperty($factory, 'instances', []);
    }
}
