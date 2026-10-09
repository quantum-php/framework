<?php

namespace Quantum\Tests\Unit\App\Stages\Request;

use Quantum\App\Stages\Request\PrepareRequestStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\ResourceCache\ViewCache;
use Quantum\App\Adapters\Web\RequestContext;
use Quantum\Router\MatchedRoute;
use Quantum\Debugger\Debugger;
use Quantum\Router\Route;
use Quantum\App\App;
use Quantum\Di\Di;

class PrepareRequestStageTest extends AppTestCase
{
    private RequestContext $requestContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestContext = new RequestContext(App::getContext());

        request()->create('GET', '/test/am/tests');

        request()->setMatchedRoute(new MatchedRoute(
            (new Route(['GET'], '/test/am/tests', 'TestController', 'tests'))->module('Test'),
            []
        ));
    }

    public function testPrepareRequestStageLoadsLanguage(): void
    {
        $this->assertFalse(config()->has('lang'));

        (new PrepareRequestStage())->process($this->requestContext);

        $this->assertTrue(config()->has('lang'));
    }

    public function testPrepareRequestStageLogsRegisteredEventsToDebugger(): void
    {
        config()->set('app.debug', true);

        $listener = function (): void {
        };

        event()->listen('test.event', $listener);

        (new PrepareRequestStage())->process($this->requestContext);

        $cell = debugbar()->getStoreCell(Debugger::EVENTS);

        $this->assertCount(1, $cell);
        $this->assertSame(['test.event' => [$listener]], $cell[0]['info']);
    }

    public function testPrepareRequestStageLogsNothingToDebuggerWithoutEventListeners(): void
    {
        config()->set('app.debug', true);

        $this->assertSame([], event()->getRegistered());

        (new PrepareRequestStage())->process($this->requestContext);

        $this->assertSame([], debugbar()->getStoreCell(Debugger::EVENTS));
    }

    public function testPrepareRequestStageSkipsDebuggerLoggingWhenDebugDisabled(): void
    {
        config()->set('app.debug', false);

        event()->listen('test.event', function (): void {
        });

        (new PrepareRequestStage())->process($this->requestContext);

        $this->assertSame([], debugbar()->getStoreCell(Debugger::EVENTS));
    }

    public function testPrepareRequestStageStoresViewCacheOnContext(): void
    {
        $this->assertNull($this->requestContext->getViewCache());

        (new PrepareRequestStage())->process($this->requestContext);

        $this->assertInstanceOf(ViewCache::class, $this->requestContext->getViewCache());
        $this->assertSame(Di::get(ViewCache::class), $this->requestContext->getViewCache());
    }

    public function testPrepareRequestStageSetsUpViewCacheWhenEnabled(): void
    {
        $viewsDir = base_dir() . DS . 'cache' . DS . 'views';
        $moduleDir = $viewsDir . DS . 'test';
        $viewsDirExisted = is_dir($viewsDir);

        $this->assertFalse(config()->has('view_cache'));

        Di::register(ViewCache::class);
        Di::get(ViewCache::class)->enableCaching(true);

        try {
            (new PrepareRequestStage())->process($this->requestContext);

            $this->assertTrue(config()->has('view_cache'));
            $this->assertDirectoryExists($moduleDir);
        } finally {
            if (is_dir($moduleDir)) {
                rmdir($moduleDir);
            }

            if (!$viewsDirExisted && is_dir($viewsDir)) {
                rmdir($viewsDir);
            }
        }
    }

    public function testPrepareRequestStageDoesNotSetUpViewCacheWhenDisabled(): void
    {
        (new PrepareRequestStage())->process($this->requestContext);

        $this->assertFalse(config()->has('view_cache'));
    }

    public function testPrepareRequestStageSkipsWhenContextIsStopped(): void
    {
        $this->requestContext->stop();

        (new PrepareRequestStage())->process($this->requestContext);

        $this->assertFalse(config()->has('lang'));
        $this->assertNull($this->requestContext->getViewCache());
    }
}
