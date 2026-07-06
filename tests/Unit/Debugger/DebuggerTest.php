<?php

namespace Quantum\Tests\Unit\Debugger;

use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use Quantum\Debugger\DebuggerStore;
use Quantum\Tests\Unit\AppTestCase;
use DebugBar\JavascriptRenderer;
use Quantum\Debugger\Debugger;
use DebugBar\DebugBar;
use Psr\Log\LogLevel;
use Mockery;

class DebuggerTest extends AppTestCase
{
    private Debugger $debugger;

    private $debugBarMock;

    private DebuggerStore $debuggerStore;

    public function setUp(): void
    {

        parent::setUp();

        $this->debuggerStore = new DebuggerStore();

        $this->debugBarMock = Mockery::mock(DebugBar::class);

        $collectors = [
            Mockery::mock(PhpInfoCollector::class),
            Mockery::mock(MessagesCollector::class),
        ];

        foreach ($collectors as $collector) {
            $this->debugBarMock->shouldReceive('addCollector')
                ->once()
                ->with($collector);
        }

        $this->debugger = new Debugger($this->debuggerStore, $this->debugBarMock, $collectors);
    }

    public function testDebuggerIsEnabled(): void
    {
        config()->set('app.debug', true);
        $this->assertTrue($this->debugger->isEnabled());

        config()->set('app.debug', false);
        $this->assertFalse($this->debugger->isEnabled());
    }

    public function testDebuggerInitStore(): void
    {
        $this->debugger->initStore();

        $this->assertTrue($this->debuggerStore->has(Debugger::MESSAGES));
        $this->assertTrue($this->debuggerStore->has(Debugger::QUERIES));
        $this->assertTrue($this->debuggerStore->has(Debugger::ROUTES));
        $this->assertTrue($this->debuggerStore->has(Debugger::HOOKS));
        $this->assertTrue($this->debuggerStore->has(Debugger::MAILS));
    }

    public function testDebuggerAddToStoreCell(): void
    {
        $this->debugger->addToStoreCell(Debugger::MESSAGES, LogLevel::INFO, 'Test message');

        $storedData = $this->debuggerStore->get(Debugger::MESSAGES);

        $this->assertEquals(['info' => 'Test message'], $storedData[0]);
    }

    public function testDebuggerGetStoreCell(): void
    {
        $this->debugger->addToStoreCell(Debugger::MESSAGES, LogLevel::INFO, 'Test message');

        $storedData = $this->debugger->getStoreCell(Debugger::MESSAGES);

        $this->assertEquals(['info' => 'Test message'], $storedData[0]);
    }

    public function testDebuggerClearStoreCell(): void
    {
        $this->debugger->addToStoreCell(Debugger::MESSAGES, LogLevel::INFO, 'Test message');
        $this->debugger->clearStoreCell(Debugger::MESSAGES);

        $storedData = $this->debuggerStore->get(Debugger::MESSAGES);

        $this->assertEmpty($storedData);
    }

    public function testDebuggerResetStore(): void
    {
        $this->debugger->initStore();

        $this->assertNotEmpty($this->debuggerStore->all());

        $this->debugger->resetStore();

        $this->assertEmpty($this->debuggerStore->all());
    }

    public function testDebugbarRender(): void
    {
        config()->set('app.debug', true);

        $this->debugger->initStore();

        $rendererMock = Mockery::mock(JavascriptRenderer::class);
        $rendererMock->shouldReceive('setBaseUrl')->andReturnSelf();
        $rendererMock->shouldReceive('addAssets')->andReturnSelf();
        $rendererMock->shouldReceive('renderHead')->andReturn('<head></head>');
        $rendererMock->shouldReceive('render')->andReturn('<div>Debug Bar</div>');

        $this->debugBarMock->shouldReceive('getJavascriptRenderer')->andReturn($rendererMock);

        $this->debugBarMock->shouldReceive('hasCollector')->with(Mockery::any())->andReturn(true);

        $output = $this->debugger->render();

        $this->assertStringContainsString('<head></head>', $output);
        $this->assertStringContainsString('<div>Debug Bar</div>', $output);
    }

    public function testDebugbarRenderReplaysArrayPayloadsThroughLog(): void
    {
        $this->debugger->initStore();
        $this->debugger->addToStoreCell(Debugger::ROUTES, LogLevel::INFO, ['Route' => '/welcome']);

        $collectorMock = Mockery::mock(MessagesCollector::class);
        $collectorMock->shouldReceive('log')
            ->once()
            ->with(LogLevel::INFO, ['Route' => '/welcome']);

        $this->debugBarMock->shouldReceive('hasCollector')
            ->with(Debugger::ROUTES)
            ->andReturn(true);
        $this->debugBarMock->shouldReceive('offsetGet')
            ->with(Debugger::ROUTES)
            ->andReturn($collectorMock);

        $method = new \ReflectionMethod($this->debugger, 'createTab');
        $method->setAccessible(true);
        $method->invoke($this->debugger, Debugger::ROUTES);

        $this->assertEquals(
            ['info' => ['Route' => '/welcome']],
            $this->debuggerStore->get(Debugger::ROUTES)[0]
        );
    }

    public function testDebugbarRenderReturnsEmptyWhenDisabled(): void
    {
        config()->set('app.debug', false);

        $output = $this->debugger->render();

        $this->assertEmpty($output);
    }
}
