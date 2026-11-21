<?php

namespace Quantum\Tests\Unit\App\Adapters\Web;

use Quantum\Router\Exceptions\RouteException;
use Quantum\App\Adapters\Web\WebAppAdapter;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Environment\Environment;
use Quantum\ResourceCache\ViewCache;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Http\Enums\ContentType;
use Quantum\Http\Enums\StatusCode;
use Quantum\Router\MatchedRoute;
use Quantum\Debugger\Debugger;
use Quantum\Router\Route;
use Quantum\Di\Di;
use ArrayObject;

class WebAppAdapterTest extends AppTestCase
{
    private WebAppAdapter $webAppAdapter;

    public function setUp(): void
    {
        $this->webAppAdapter = new WebAppAdapter($this->createContext());
        $this->webAppAdapter->boot();
    }

    public function tearDown(): void
    {
        $this->removeFrameworkErrorHandlers();
        if (Di::isRegistered(Debugger::class)) {
            Di::get(Debugger::class)->resetStore();
        }

        config()->flush();
        $this->clearAppContext();
    }

    public function testRemoveFrameworkErrorHandlersRemovesTheHandlersOfEveryBoot(): void
    {
        (new WebAppAdapter($this->createContext()))->boot();

        $this->removeFrameworkErrorHandlers();

        $errorHandler = set_error_handler(static fn (): bool => false);
        restore_error_handler();

        $exceptionHandler = set_exception_handler(static function (): void {
        });
        restore_exception_handler();

        foreach ([$errorHandler, $exceptionHandler] as $handler) {
            $this->assertFalse(
                $handler instanceof \Closure && (new \ReflectionFunction($handler))->getClosureThis() instanceof \Quantum\Tracer\ErrorHandler,
                'A handler of Quantum\Tracer\ErrorHandler is still registered'
            );
        }
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

    public function testWebAppAdapterStartHandlesOptionsPreflight(): void
    {
        request()->create('OPTIONS', '/test/am/tests');

        ob_start();
        $result = $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertSame(0, $result);
        $this->assertSame(StatusCode::NO_CONTENT, response()->getStatusCode());
        $this->assertSame('', response()->getContent());
        $this->assertSame('*', response()->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('*', response()->getHeader('Access-Control-Allow-Methods'));
        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    public function testWebAppAdapterStartSkipsLanguageLoadingForPreflight(): void
    {
        request()->create('OPTIONS', '/test/am/tests');

        $this->assertFalse(config()->has('lang'));

        ob_start();
        $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertFalse(config()->has('lang'));
    }

    public function testWebAppAdapterStartSkipsLanguageLoadingForNotFound(): void
    {
        request()->create('GET', '/non-existing-uri');

        $this->assertFalse(config()->has('lang'));

        ob_start();
        $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertFalse(config()->has('lang'));
    }

    public function testWebAppAdapterStartRespondsWithJsonNotFoundForJsonRequests(): void
    {
        request()->create('GET', '/non-existing-uri', [], ['Accept' => 'application/json']);

        ob_start();
        $result = $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertSame(0, $result);
        $this->assertSame(StatusCode::NOT_FOUND, response()->getStatusCode());
        $this->assertSame(ContentType::JSON, response()->getContentType());
        $this->assertStringContainsString('Page not found', response()->getContent());
        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    public function testWebAppAdapterStartRespondsWithJsonNotFoundWhenAcceptListsJson(): void
    {
        request()->create('GET', '/non-existing-uri', [], ['Accept' => 'application/json, text/plain, */*']);

        ob_start();
        $result = $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertSame(0, $result);
        $this->assertSame(StatusCode::NOT_FOUND, response()->getStatusCode());
        $this->assertSame(ContentType::JSON, response()->getContentType());
        $this->assertStringContainsString('Page not found', response()->getContent());
    }

    public function testWebAppAdapterStartRespondsWithHtmlNotFoundForOtherRequests(): void
    {
        request()->create('GET', '/non-existing-uri');

        ob_start();
        $result = $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertSame(0, $result);
        $this->assertSame(StatusCode::NOT_FOUND, response()->getStatusCode());
        $this->assertSame(ContentType::HTML, response()->getContentType());
        $this->assertNotSame('', response()->getContent());
        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    public function testWebAppAdapterStartSendsMatchedRouteResponse(): void
    {
        request()->create('GET', '/test/am/tests');

        ob_start();
        $result = $this->webAppAdapter->start();
        $output = ob_get_clean();

        $this->assertSame(0, $result);
        $this->assertSame(StatusCode::OK, response()->getStatusCode());
        $this->assertSame('', response()->getContent());
        $this->assertSame('', $output);
        $this->assertSame('*', response()->getHeader('Access-Control-Allow-Origin'));
        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    public function testWebAppAdapterStartServesViewCacheHit(): void
    {
        request()->create('GET', '/test/am/tests');

        $uri = request()->getUri();

        $viewsDir = base_dir() . DS . 'cache' . DS . 'views';
        $moduleDir = $viewsDir . DS . 'test';
        $viewsDirExisted = is_dir($viewsDir);
        $moduleDirExisted = is_dir($moduleDir);

        // The view cache directory depends on the current module, which is only known once a route is matched.
        request()->setMatchedRoute(new MatchedRoute(
            (new Route(['GET'], '/test/am/tests', 'TestController', 'tests'))->module('Test'),
            []
        ));

        if (!Di::isRegistered(ViewCache::class)) {
            Di::register(ViewCache::class);
        }

        $viewCache = Di::get(ViewCache::class);
        $viewCache->setup();
        $viewCache->enableCaching(true);
        $viewCache->set($uri, '<p>cached page</p>');

        try {
            ob_start();
            $result = $this->webAppAdapter->start();
            $output = ob_get_clean();

            $this->assertSame(0, $result);
            $this->assertSame('<p>cached page</p>', response()->getContent());
            $this->assertSame('<p>cached page</p>', $output);
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

    public function testWebAppAdapterStartPropagatesDispatchExceptionWithoutCleanup(): void
    {
        request()->create('GET', '/test/Test/5');

        ob_start();

        try {
            $this->webAppAdapter->start();
            $this->fail('Expected the dispatcher to fail for a missing controller action.');
        } catch (RouteException $exception) {
            $this->assertInstanceOf(RouteException::class, $exception);
        } finally {
            ob_end_clean();
        }

        $this->assertNotNull(request()->getMatchedRoute());
        $this->assertNotNull(request()->getUri());
    }

    public function testWebAppAdapterStartDispatchesTheEventsOfEveryRuntimeStageForAMatchedRoute(): void
    {
        request()->create('GET', '/test/am/tests');

        $events = $this->recordRuntimeEvents();

        ob_start();
        $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertSame([
            'web.preflight.before', 'web.preflight.after',
            'web.route.before', 'web.route.after',
            'web.not_found.before', 'web.not_found.after',
            'web.prepare.before', 'web.prepare.after',
            'web.dispatch.before', 'web.dispatch.after',
            'web.send.before', 'web.send.after',
        ], $events->getArrayCopy());
    }

    public function testWebAppAdapterStartDispatchesOnlyThePreflightAndSendEventsForAnOptionsRequest(): void
    {
        request()->create('OPTIONS', '/test/am/tests');

        $events = $this->recordRuntimeEvents();

        ob_start();
        $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertSame([
            'web.preflight.before', 'web.preflight.after',
            'web.send.before', 'web.send.after',
        ], $events->getArrayCopy());
    }

    public function testWebAppAdapterStartDispatchesNoPrepareOrDispatchEventsWhenNoRouteMatches(): void
    {
        request()->create('GET', '/non-existing-uri');

        $events = $this->recordRuntimeEvents();

        ob_start();
        $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertSame([
            'web.preflight.before', 'web.preflight.after',
            'web.route.before', 'web.route.after',
            'web.not_found.before', 'web.not_found.after',
            'web.send.before', 'web.send.after',
        ], $events->getArrayCopy());
    }

    public function testWebAppAdapterStartDispatchesNoAfterEventAndNoSendEventsWhenAStageThrows(): void
    {
        request()->create('GET', '/test/Test/5');

        $events = $this->recordRuntimeEvents();

        ob_start();

        try {
            $this->webAppAdapter->start();
            $this->fail('Expected the dispatcher to fail for a missing controller action.');
        } catch (RouteException $exception) {
            $this->assertSame([
                'web.preflight.before', 'web.preflight.after',
                'web.route.before', 'web.route.after',
                'web.not_found.before', 'web.not_found.after',
                'web.prepare.before', 'web.prepare.after',
                'web.dispatch.before',
            ], $events->getArrayCopy());
        } finally {
            ob_end_clean();
        }
    }

    public function testWebAppAdapterStartPassesTheWebContextToTheRuntimeEvents(): void
    {
        request()->create('GET', '/test/am/tests');

        $contexts = [];

        foreach (['web.preflight.before', 'web.send.after'] as $name) {
            event()->listen($name, function (array $payload) use (&$contexts): void {
                $contexts[] = $payload['context'];
            });
        }

        ob_start();
        $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertCount(2, $contexts);
        $this->assertInstanceOf(WebContext::class, $contexts[0]);
        $this->assertSame($contexts[0], $contexts[1]);
    }

    public function testWebAppAdapterStartDispatchesTheSendAfterEventOnceTheRequestIsCleared(): void
    {
        request()->create('GET', '/test/am/tests');

        $uriSeenAfterSend = 'not called';

        event()->listen('web.send.after', function () use (&$uriSeenAfterSend): void {
            $uriSeenAfterSend = request()->getUri();
        });

        ob_start();
        $this->webAppAdapter->start();
        ob_end_clean();

        $this->assertNull($uriSeenAfterSend);
    }

    public function testWebAppAdapterConstructorDoesNotBoot(): void
    {
        $this->clearAppContext();

        new WebAppAdapter($this->createContext());

        $this->assertFalse(Di::has(Environment::class));
    }

    public function testWebAppAdapterBootRunsTheBootStages(): void
    {
        $this->clearAppContext();

        $adapter = new WebAppAdapter($this->createContext());
        $adapter->boot();

        $this->assertTrue(Di::has(Environment::class));
        $this->assertTrue(config()->has('app'));
    }

    public function testWebAppAdapterStartFiresAppHelperListenerAtPreflightBeforeEvent(): void
    {
        $this->withTemporaryAppHelper(function (): void {
            $this->startWithAppHelperListener();

            $this->assertCount(1, $GLOBALS['helperEventPayloads']);
            $this->assertInstanceOf(WebContext::class, $GLOBALS['helperEventPayloads'][0]['context']);
        });
    }

    public function testWebAppAdapterBootRegistersAppHelperListenerBeforeAppConfigIsLoaded(): void
    {
        $this->withTemporaryAppHelper(function (): void {
            (new WebAppAdapter($this->createContext()))->boot();

            $this->assertFalse($GLOBALS['helperConfigLoadedAtRegistration']);
            $this->assertTrue(config()->has('app'));
        });
    }

    public function testWebAppAdapterSecondBootDoesNotRegisterAppHelperListenerAgain(): void
    {
        $this->withTemporaryAppHelper(function (): void {
            $this->startWithAppHelperListener();
            $this->assertCount(1, $GLOBALS['helperEventPayloads']);

            // Helper files load with require_once, so a new container does not get the listener again.
            $this->startWithAppHelperListener();
            $this->assertCount(1, $GLOBALS['helperEventPayloads']);
        });
    }

    /**
     * Boots a new adapter, which loads the temporary app helper, and starts it for a matched route
     */
    private function startWithAppHelperListener(): void
    {
        $adapter = new WebAppAdapter($this->createContext());
        $adapter->boot();

        request()->create('GET', '/test/am/tests');

        ob_start();
        $adapter->start();
        ob_end_clean();
    }

    /**
     * Listens to the before and after events of every web runtime stage and records their names in order
     * @return ArrayObject<int, string>
     */
    private function recordRuntimeEvents(): ArrayObject
    {
        $events = new ArrayObject();

        foreach (['preflight', 'route', 'not_found', 'prepare', 'dispatch', 'send'] as $stage) {
            foreach (['before', 'after'] as $point) {
                $name = 'web.' . $stage . '.' . $point;

                event()->listen($name, function () use ($events, $name): void {
                    $events[] = $name;
                });
            }
        }

        return $events;
    }

    private function withTemporaryAppHelper(callable $test): void
    {
        $dir = PROJECT_ROOT . DS . 'helpers';
        $createdDir = !is_dir($dir);
        $file = $dir . DS . uniqid('event_listener_helper_') . '.php';

        $helper = <<<'PHP'
<?php

$GLOBALS['helperConfigLoadedAtRegistration'] = config()->has('app');

event()->listen('web.preflight.before', function (array $payload): void {
    $GLOBALS['helperEventPayloads'][] = $payload;
});
PHP;

        try {
            if ($createdDir) {
                $this->assertTrue(mkdir($dir));
            }

            $this->assertNotFalse(file_put_contents($file, $helper));

            $test();
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
            if ($createdDir && is_dir($dir)) {
                rmdir($dir);
            }
            unset($GLOBALS['helperEventPayloads'], $GLOBALS['helperConfigLoadedAtRegistration']);
        }
    }
}
