<?php

namespace Quantum\Tests\Unit\Router;

use Quantum\Router\Exceptions\RouteException;
use Quantum\Csrf\Exceptions\CsrfException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\RouteDispatcher;
use Quantum\Router\MatchedRoute;
use Quantum\Http\Response;
use Quantum\Router\Route;
use Quantum\Http\Request;
use Quantum\Csrf\Csrf;
use Quantum\Di\Di;
use Mockery;

class RouteDispatcherTest extends AppTestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testDispatchExecutesClosureRoute(): void
    {
        $expectedResponse = response();

        $closure = function (string $id, Response $response) use ($expectedResponse): Response {
            $this->assertSame('123', $id);
            $this->assertSame($expectedResponse, $response);

            return $response->json([
                'id' => $id,
            ]);
        };

        $route = new Route(['GET'], '/test', null, null, $closure);

        $matched = new MatchedRoute(
            $route,
            ['id' => '123']
        );

        $dispatcher = new RouteDispatcher();

        $request = Mockery::mock(Request::class);
        $response = $dispatcher->dispatch($matched, $request);

        $this->assertSame($expectedResponse, $response);
        $this->assertSame('{"id":"123"}', $response->getContent());
    }

    public function testDispatchThrowsWhenClosureRouteHasNoClosure(): void
    {
        $this->expectException(RouteException::class);

        $route = new Route(['GET'], '/broken', null, null);

        $matched = new MatchedRoute($route, []);

        $dispatcher = new RouteDispatcher();

        $request = Mockery::mock(Request::class);
        Mockery::mock(Response::class);

        $dispatcher->dispatch($matched, $request);
    }

    public function testDispatchExecutesControllerActionWithParams(): void
    {
        $controllerClass = new class () {
            public static ?string $received = null;

            public function post(string $uuid, Response $response): Response
            {
                self::$received = $uuid;

                return $response->json([
                    'uuid' => $uuid,
                ]);
            }
        };

        $route = new Route(['GET'], '[:alpha:2]?/post/[uuid=:any]', get_class($controllerClass), 'post');

        $matched = new MatchedRoute($route, ['uuid' => 'abc-123']);

        $dispatcher = new RouteDispatcher();

        $request = Mockery::mock(Request::class);

        $response = $dispatcher->dispatch($matched, $request);

        $this->assertSame(
            'abc-123',
            $controllerClass::$received,
            'Controller action did not receive matched parameters'
        );
        $this->assertSame('{"uuid":"abc-123"}', $response->getContent());
    }

    public function testDispatchCallsControllerHooksInCorrectOrder(): void
    {
        $controllerClass = new class () {
            public static array $calls = [];

            public function __before(string $uuid): void
            {
                self::$calls[] = 'before:' . $uuid;
            }

            public function post(string $uuid, Response $response): Response
            {
                self::$calls[] = 'action:' . $uuid;

                return $response->json([
                    'uuid' => $uuid,
                ]);
            }

            public function __after(string $uuid): void
            {
                self::$calls[] = 'after:' . $uuid;
            }
        };

        $route = new Route(['GET', 'POST'], '[:alpha:2]?/post/[uuid=:any]', get_class($controllerClass), 'post');

        $matched = new MatchedRoute($route, ['uuid' => 'abc']);

        $dispatcher = new RouteDispatcher();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('POST');

        $response = $dispatcher->dispatch($matched, $request);

        $this->assertSame(
            [
                'before:abc',
                'action:abc',
                'after:abc',
            ],
            $controllerClass::$calls,
            'Controller hooks were not executed in the correct order'
        );
        $this->assertSame('{"uuid":"abc"}', $response->getContent());
    }

    public function testDispatchThrowsWhenClosureRouteDoesNotReturnResponse(): void
    {
        $this->expectException(RouteException::class);

        $route = new Route(['GET'], '/broken', null, null, function (): string {
            return 'invalid';
        });

        $matched = new MatchedRoute($route, []);

        $dispatcher = new RouteDispatcher();

        $request = Mockery::mock(Request::class);

        $dispatcher->dispatch($matched, $request);
    }

    public function testDispatchThrowsWhenControllerActionDoesNotReturnResponse(): void
    {
        $this->expectException(RouteException::class);

        $controllerClass = new class () {
            public function post(): void
            {
            }
        };

        $route = new Route(['GET'], '/invalid', get_class($controllerClass), 'post');
        $matched = new MatchedRoute($route, []);
        $dispatcher = new RouteDispatcher();
        $request = Mockery::mock(Request::class);

        $dispatcher->dispatch($matched, $request);
    }

    public function testDispatchThrowsWhenControllerActionDoesNotExist(): void
    {
        $this->expectException(RouteException::class);

        $controllerClass = new class () {
            // Intentionally no action method
        };

        $route = new Route(['GET'], '[:alpha:2]?/broken', get_class($controllerClass), 'missingAction');

        $matched = new MatchedRoute($route, []);

        $dispatcher = new RouteDispatcher();

        $request = Mockery::mock(Request::class);
        Mockery::mock(Response::class);

        $dispatcher->dispatch($matched, $request);
    }

    public function testDispatchKeepsFrameworkParamOutOfPositionalArguments(): void
    {
        $controllerClass = new class () {
            public static ?string $received = null;

            public function post(string $uuid, Response $response): Response
            {
                self::$received = $uuid;

                return $response;
            }
        };

        $route = new Route(['GET'], '[:alpha:2]?/post/[uuid=:any]', get_class($controllerClass), 'post');
        $matched = new MatchedRoute($route, ['_segment0' => 'es', 'uuid' => 'abc']);

        (new RouteDispatcher())->dispatch($matched, Mockery::mock(Request::class), ['_segment0' => 'lang']);

        $this->assertSame('abc', $controllerClass::$received);
    }

    public function testDispatchPassesFrameworkParamByName(): void
    {
        $controllerClass = new class () {
            public static array $received = [];

            public function post(?string $lang, string $uuid, Response $response): Response
            {
                self::$received = [$lang, $uuid];

                return $response;
            }
        };

        $route = new Route(['GET'], '[:alpha:2]?/post/[uuid=:any]', get_class($controllerClass), 'post');
        $matched = new MatchedRoute($route, ['_segment0' => 'es', 'uuid' => 'abc']);

        (new RouteDispatcher())->dispatch($matched, Mockery::mock(Request::class), ['_segment0' => 'lang']);

        $this->assertSame(['es', 'abc'], $controllerClass::$received);
    }

    public function testDispatchWithoutFrameworkParamsBindsAllParamsPositionally(): void
    {
        $controllerClass = new class () {
            public static array $received = [];

            public function post(?string $lang, string $uuid, Response $response): Response
            {
                self::$received = [$lang, $uuid];

                return $response;
            }
        };

        $route = new Route(['GET'], '[:alpha:2]?/post/[uuid=:any]', get_class($controllerClass), 'post');
        $matched = new MatchedRoute($route, ['_segment0' => 'es', 'uuid' => 'abc']);

        (new RouteDispatcher())->dispatch($matched, Mockery::mock(Request::class));

        $this->assertSame(['es', 'abc'], $controllerClass::$received);
    }

    public function testDispatchIgnoresFrameworkParamTheRouteDoesNotHave(): void
    {
        $controllerClass = new class () {
            public static ?string $received = null;

            public function post(string $uuid, Response $response): Response
            {
                self::$received = $uuid;

                return $response;
            }
        };

        $route = new Route(['GET'], 'post/[uuid=:any]', get_class($controllerClass), 'post');
        $matched = new MatchedRoute($route, ['uuid' => 'abc']);

        (new RouteDispatcher())->dispatch($matched, Mockery::mock(Request::class), ['_segment0' => 'lang']);

        $this->assertSame('abc', $controllerClass::$received);
    }

    public function testDispatchKeepsFrameworkParamOutOfHookArguments(): void
    {
        $controllerClass = new class () {
            public static array $calls = [];

            public function __before(string $uuid): void
            {
                self::$calls[] = 'before:' . $uuid;
            }

            public function post(string $uuid, Response $response): Response
            {
                self::$calls[] = 'action:' . $uuid;

                return $response;
            }

            public function __after(string $uuid, ?string $lang): void
            {
                self::$calls[] = 'after:' . $uuid . ':' . $lang;
            }
        };

        $route = new Route(['GET'], '[:alpha:2]?/post/[uuid=:any]', get_class($controllerClass), 'post');
        $matched = new MatchedRoute($route, ['_segment0' => 'es', 'uuid' => 'abc']);

        (new RouteDispatcher())->dispatch($matched, Mockery::mock(Request::class), ['_segment0' => 'lang']);

        $this->assertSame(['before:abc', 'action:abc', 'after:abc:es'], $controllerClass::$calls);
    }

    public function testDispatchKeepsFrameworkParamOutOfClosureArguments(): void
    {
        $received = [];

        $closure = function (string $id, Response $response, ?string $lang = null) use (&$received): Response {
            $received = [$id, $lang];

            return $response;
        };

        $route = new Route(['GET'], '[:alpha:2]?/item/[id=:num]', null, null, $closure);
        $matched = new MatchedRoute($route, ['_segment0' => 'es', 'id' => '7']);

        (new RouteDispatcher())->dispatch($matched, Mockery::mock(Request::class), ['_segment0' => 'lang']);

        $this->assertSame(['7', 'es'], $received);
    }

    public function testDispatchFailsWhenCsrfIsEnabledAndTokenIsMissing(): void
    {
        $this->expectException(CsrfException::class);

        $controllerClass = new class () {
            public bool $csrfVerification = true;

            public function submit(): void
            {
                // noop
            }
        };

        $route = new Route(['POST'], '[:alpha:2]?/submit', get_class($controllerClass), 'submit');

        $matched = new MatchedRoute($route, []);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('POST');
        $request->shouldReceive('has')
            ->with(Csrf::TOKEN_KEY)
            ->andReturn(false);

        $csrf = Mockery::mock(Csrf::class);
        $csrf->shouldReceive('checkToken')
            ->with($request)
            ->andThrow(new CsrfException('Missing CSRF token'));

        Di::set(Csrf::class, $csrf);

        $dispatcher = new RouteDispatcher();

        $dispatcher->dispatch($matched, $request);
    }
}
