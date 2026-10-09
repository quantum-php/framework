<?php

namespace Quantum\Tests\_root\modules\Test\Middlewares {
    use Quantum\Middleware\Middleware;
    use Quantum\Http\Request;
    use Quantum\Http\Response;
    use Closure;

    class DispatchStageMwPassThrough extends Middleware
    {
        public static array $calls = [];

        public function apply(Request $request, Closure $next): Response
        {
            self::$calls[] = 'DispatchStageMwPassThrough';

            return $next($request);
        }
    }

    class DispatchStageMwBlocker extends Middleware
    {
        public function apply(Request $request, Closure $next): Response
        {
            return response()->json(['status' => 'blocked']);
        }
    }
}

namespace Quantum\Tests\Unit\App\Stages\Request {
    use Quantum\Tests\_root\modules\Test\Middlewares\DispatchStageMwPassThrough;
    use Quantum\App\Stages\Request\DispatchRequestStage;
    use Quantum\App\Stages\Request\ResolveRouteStage;
    use Quantum\Router\Exceptions\RouteException;
    use Quantum\Tests\Unit\AppTestCase;
    use Quantum\ResourceCache\ViewCache;
    use Quantum\App\Adapters\Web\RequestContext;
    use Quantum\Router\MatchedRoute;
    use Quantum\Http\Enums\StatusCode;
    use Quantum\Router\Route;
    use Quantum\App\App;
    use RuntimeException;
    use Quantum\Di\Di;

    class DispatchRequestStageTest extends AppTestCase
    {
        private RequestContext $requestContext;

        public function setUp(): void
        {
            parent::setUp();

            $this->requestContext = new RequestContext(App::getContext());

            DispatchStageMwPassThrough::$calls = [];
        }

        public function testDispatchRequestStageDispatchesMatchedRoute(): void
        {
            $this->prepareRequest('/test/am/tests');

            (new DispatchRequestStage())->process($this->requestContext);

            $this->assertSame(response(), $this->requestContext->getResponse());
            $this->assertSame(StatusCode::OK, $this->requestContext->getResponse()->getStatusCode());
            $this->assertSame('', $this->requestContext->getResponse()->getContent());
        }

        public function testDispatchRequestStageServesViewCacheHit(): void
        {
            $this->prepareRequest('/test/am/tests');

            $uri = request()->getUri();
            $viewsDir = base_dir() . DS . 'cache' . DS . 'views';
            $moduleDir = $viewsDir . DS . 'test';
            $viewsDirExisted = is_dir($viewsDir);
            $moduleDirExisted = is_dir($moduleDir);

            $viewCache = $this->requestContext->getViewCache();
            $viewCache->setup();
            $viewCache->enableCaching(true);
            $viewCache->set($uri, '<p>cached page</p>');

            try {
                (new DispatchRequestStage())->process($this->requestContext);

                $this->assertSame('<p>cached page</p>', $this->requestContext->getResponse()->getContent());
            } finally {
                $viewCache->delete($uri);
                $viewCache->enableCaching(false);

                if (!$moduleDirExisted && is_dir($moduleDir)) {
                    rmdir($moduleDir);
                }

                if (!$viewsDirExisted && is_dir($viewsDir)) {
                    rmdir($viewsDir);
                }
            }
        }

        public function testDispatchRequestStageRunsModuleMiddlewaresBeforeController(): void
        {
            $this->prepareRequest('/test/am/tests');

            $this->requestContext->getMatchedRoute()->getRoute()->addMiddlewares(['DispatchStageMwPassThrough']);

            (new DispatchRequestStage())->process($this->requestContext);

            $this->assertSame(['DispatchStageMwPassThrough'], DispatchStageMwPassThrough::$calls);
            $this->assertSame(StatusCode::OK, $this->requestContext->getResponse()->getStatusCode());
        }

        public function testDispatchRequestStageLetsMiddlewareShortCircuit(): void
        {
            $this->prepareRequest('/test/am/tests');

            // The controller does not exist, so reaching the dispatcher would throw.
            $matchedRoute = new MatchedRoute($this->createRoute('tests', ['DispatchStageMwBlocker'], 'MissingController'), []);

            $this->requestContext->setMatchedRoute($matchedRoute);
            request()->setMatchedRoute($matchedRoute);

            (new DispatchRequestStage())->process($this->requestContext);

            $this->assertStringContainsString('blocked', $this->requestContext->getResponse()->getContent());
        }

        public function testDispatchRequestStagePropagatesDispatchException(): void
        {
            $this->prepareRequest('/test/Test/5');

            try {
                (new DispatchRequestStage())->process($this->requestContext);
                $this->fail('Expected the dispatcher to fail for a missing controller action.');
            } catch (RouteException $exception) {
                $this->assertInstanceOf(RouteException::class, $exception);
            }

            $this->assertNull($this->requestContext->getResponse());
        }

        public function testDispatchRequestStageFailsWithoutMatchedRoute(): void
        {
            $this->requestContext->setViewCache($this->viewCache());

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Matched route is not set.');

            (new DispatchRequestStage())->process($this->requestContext);
        }

        public function testDispatchRequestStageFailsWithoutViewCache(): void
        {
            $this->requestContext->setMatchedRoute(new MatchedRoute($this->createRoute('tests'), []));

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('View cache is not set.');

            (new DispatchRequestStage())->process($this->requestContext);
        }

        public function testDispatchRequestStageSkipsWhenContextIsStopped(): void
        {
            $this->prepareRequest('/test/am/tests');

            $this->requestContext->stop();

            (new DispatchRequestStage())->process($this->requestContext);

            $this->assertNull($this->requestContext->getResponse());
        }

        private function prepareRequest(string $uri): void
        {
            request()->create('GET', $uri);

            (new ResolveRouteStage())->process($this->requestContext);

            $this->requestContext->setViewCache($this->viewCache());
        }

        private function viewCache(): ViewCache
        {
            if (!Di::isRegistered(ViewCache::class)) {
                Di::register(ViewCache::class);
            }

            return Di::get(ViewCache::class);
        }

        /**
         * @param string[] $middlewares
         */
        private function createRoute(string $action, array $middlewares = [], string $controller = 'TestController'): Route
        {
            $route = (new Route(['GET'], '/test/am/tests', $controller, $action))->module('Test');

            if ($middlewares !== []) {
                $route->addMiddlewares($middlewares);
            }

            return $route;
        }
    }
}
