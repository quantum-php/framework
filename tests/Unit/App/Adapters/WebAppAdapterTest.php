<?php

namespace Quantum\Tests\Unit\App\Adapters;

use Quantum\Http\Exceptions\HttpException;
use Quantum\App\Adapters\WebAppAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\Route;
use Throwable;

class WebAppAdapterTest extends AppTestCase
{
    private WebAppAdapter $webAppAdapter;

    public function setUp(): void
    {
        $this->webAppAdapter = new WebAppAdapter($this->createContext());
    }

    public function tearDown(): void
    {
        config()->flush();
        $this->clearAppContext();
    }

    public function testWebAppAdapterStartSuccessfully(): void
    {
        request()->create('GET', '/test/am/tests');
        $this->assertFalse(config()->has('lang'));

        ob_start();
        $result = $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertEquals(0, $result);
        $this->assertTrue(config()->has('lang'));
        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    public function testWebAppAdapterStartFails(): void
    {
        request()->create('POST', '');

        ob_start();
        $result = $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertSame(0, $result);
        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    public function testWebAppAdapterHandlesPageNotFoundGracefully(): void
    {
        request()->create('GET', '/non-existing-uri');

        ob_start();
        $result = $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertSame(0, $result);
        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    public function testWebAppAdapterCleansUpOnException(): void
    {
        request()->create('GET', '/test/am/tests');
        request()->setMatchedRoute(null);
        request()->setMatchedRoute(new \Quantum\Router\MatchedRoute(
            new Route(['GET'], '/test/am/tests', 'TestController', 'tests'),
            []
        ));
        response()->setHeader('X-Test', '1');
        response()->json(['foo' => 'bar']);

        $throwingResponse = new class () extends \Quantum\Http\Response {
            public function send(): void
            {
                throw new HttpException('boom');
            }
        };

        try {
            $this->invokePrivateMethod($this->webAppAdapter, 'sendResponse', [$throwingResponse]);
            $this->fail('Expected response sending to fail.');
        } catch (Throwable $exception) {
            $this->assertInstanceOf(HttpException::class, $exception);
        }

        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
        $this->assertSame(['foo' => 'bar'], response()->all());
        $this->assertSame('1', response()->getHeader('X-Test'));
        $this->assertSame(200, response()->getStatusCode());
    }
}
