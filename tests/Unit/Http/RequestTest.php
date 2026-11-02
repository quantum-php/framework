<?php

namespace Quantum\Tests\Unit\Http;

use Quantum\Router\RouteCollection;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\MatchedRoute;
use InvalidArgumentException;
use Quantum\Router\Route;
use Quantum\Di\Di;

class RequestTest extends AppTestCase
{
    public function testSetRejectsTheReservedRenderedViewKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot set reserved key: `_qt_rendered_view`');

        request()->set('_qt_rendered_view', 'content');
    }

    public function testSetGetMethod(): void
    {
        $request = request();

        $request->create('GET', '/');

        $this->assertEquals('GET', $request->getMethod());

        $request->setMethod('POST');

        $this->assertEquals('POST', $request->getMethod());
    }

    public function testIsMethod(): void
    {
        $request = request();

        $request->create('GET', '/');

        $this->assertTrue($request->isMethod('GET'));

        $this->assertTrue($request->isMethod('get'));

        $this->assertFalse($request->isMethod('POST'));

        $request->setMethod('POST');

        $this->assertTrue($request->isMethod('POST'));

        $this->assertTrue($request->isMethod('post'));
    }

    public function testGetCsrfToken(): void
    {
        $request = request();

        $this->assertNull($request->getCsrfToken());

        $request->create('PATCH', '/', ['csrf-token' => csrf_token()]);

        $this->assertNotNull($request->getCsrfToken());

    }

    public function testRouteMetadataProxyMethods(): void
    {
        $route = new Route(['GET'], 'post/[uuid=:any]', 'PostController', 'show');
        $route
            ->module('Web')
            ->prefix('api')
            ->group('content')
            ->name('post.show')
            ->addMiddlewares(['Auth'])
            ->cache(true, 60)
            ->setCompiledPattern('compiled-pattern');

        request()->setMatchedRoute(new MatchedRoute($route, ['uuid' => 'abc-123']));

        $this->assertSame(['Auth'], request()->getCurrentMiddlewares());
        $this->assertSame('Web', request()->getCurrentModule());
        $this->assertSame('PostController', request()->getCurrentController());
        $this->assertSame('show', request()->getCurrentAction());
        $this->assertSame('post/[uuid=:any]', request()->getCurrentRoutePattern());
        $this->assertSame('compiled-pattern', request()->getCompiledRoutePattern());
        $this->assertSame(['uuid' => 'abc-123'], request()->getRouteParams());
        $this->assertSame('abc-123', request()->getRouteParam('uuid'));
        $this->assertSame(['enabled' => true, 'ttl' => 60], request()->getRouteCacheSettings());
        $this->assertSame('post.show', request()->getRouteName());
        $this->assertSame('api', request()->getRoutePrefix());
    }

    public function testCompiledRoutePatternReturnsNullWhenNoRouteMatched(): void
    {
        request()->setMatchedRoute(null);
        $this->assertNull(request()->getCompiledRoutePattern());
    }

    public function testRouteCollectionProxyMethods(): void
    {
        $route = new Route(['GET'], 'dashboard', 'DashboardController', 'index');
        $route->name('dashboard')->group('auth')->module('web');

        $collection = Di::isRegistered(RouteCollection::class)
            ? Di::get(RouteCollection::class)
            : new RouteCollection();
        $collection->add($route);

        if (!Di::isRegistered(RouteCollection::class)) {
            Di::set(RouteCollection::class, $collection);
        }

        $this->assertInstanceOf(Route::class, request()->findRouteByName('dashboard', 'web'));
        $this->assertTrue(request()->routeGroupExists('auth', 'web'));
        $this->assertFalse(request()->routeGroupExists('guest', 'web'));
    }
}
