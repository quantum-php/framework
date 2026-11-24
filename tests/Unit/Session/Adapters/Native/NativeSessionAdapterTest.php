<?php

namespace Quantum\Tests\Unit\Session\Adapters\Native;

use Quantum\Session\Adapters\Native\NativeSessionAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\ConfigSection;

class NativeSessionAdapterTest extends AppTestCase
{
    private NativeSessionAdapter $session;

    public function setUp(): void
    {
        parent::setUp();

        $this->session = new NativeSessionAdapter();

        $this->session->delete('LAST_ACTIVITY');
    }

    public function testNativeSessionConstructor(): void
    {
        $this->assertInstanceOf(NativeSessionAdapter::class, $this->session);
    }

    public function testNativeSessionSessionAll(): void
    {
        $this->assertEmpty($this->session->all());

        $this->session->set('test', 'Test data');

        $this->session->set('user', ['username' => 'test@unit.com']);

        $this->assertNotEmpty($this->session->all());

        $this->assertIsArray($this->session->all());

        $this->assertArrayHasKey('test', $this->session->all());

        $this->assertEquals('Test data', $this->session->all()['test']);
    }

    public function testNativeSessionGetSetHasDelete(): void
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

    public function testNativeSessionGetSetFlash(): void
    {
        $this->session->setFlash('message', 'Flash message');

        $this->assertEquals('Flash message', $this->session->getFlash('message'));

        $this->assertNull($this->session->getFlash('message'));
    }

    public function testNativeSessionFlush(): void
    {
        $this->session->set('test', 'Test data');

        $this->assertNotEmpty($this->session->all());

        $this->session->flush();

        $this->assertEmpty($this->session->all());

        session_start();
    }

    public function testNativeSessionFlushWithoutAnActiveSessionDoesNotWarn(): void
    {
        $oldLevel = error_reporting(E_ALL);

        try {
            $this->session->flush();

            $this->session->flush();

            $this->assertEmpty($this->session->all());
        } finally {
            error_reporting($oldLevel);

            @session_start();
        }
    }

    public function testNativeSessionGetSessionId(): void
    {
        $this->assertEquals(session_id(), $this->session->getId());
    }

    public function testNativeSessionRegenerateSessionId(): void
    {
        $sessionId = $this->session->getId();

        $this->assertEquals(session_id(), $sessionId);

        $this->session->regenerateId();

        $this->assertNotEquals(session_id(), $sessionId);
    }

    public function testNativeSessionKeepsItsConfigAfterRegenerateId(): void
    {
        $session = new NativeSessionAdapter(new ConfigSection(['timeout' => '60']));

        $session->regenerateId();

        $config = $this->getPrivateProperty($session, 'config');

        $this->assertInstanceOf(ConfigSection::class, $config);
        $this->assertSame(60, $config->getInt('timeout'));
    }
}
