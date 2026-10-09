<?php

namespace Quantum\Tests\Unit\App\Adapters\Web;

use Quantum\Router\Exceptions\RouteException;
use Quantum\App\Adapters\Web\WebAppAdapter;
use Quantum\ResourceCache\ViewCache;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Http\Enums\ContentType;
use Quantum\Http\Enums\StatusCode;
use Quantum\Router\MatchedRoute;
use Quantum\Debugger\Debugger;
use Quantum\Router\Route;
use Quantum\Di\Di;

class WebAppAdapterTest extends AppTestCase
{
    private WebAppAdapter $webAppAdapter;

    public function setUp(): void
    {
        $this->webAppAdapter = new WebAppAdapter($this->createContext());
    }

    public function tearDown(): void
    {
        if (Di::isRegistered(Debugger::class)) {
            Di::get(Debugger::class)->resetStore();
        }

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

    public function testWebAppAdapterBootFiresAppHelperListenerAtModulesBeforeEvent(): void
    {
        $this->withTemporaryAppHelper(function (): void {
            $context = $this->createContext();

            new WebAppAdapter($context);

            $this->assertCount(1, $GLOBALS['bootEventPayloads']);
            $this->assertSame($context, $GLOBALS['bootEventPayloads'][0]['context']);
        });
    }

    public function testWebAppAdapterBootRegistersAppHelperListenerBeforeAppConfigIsLoaded(): void
    {
        $this->withTemporaryAppHelper(function (): void {
            new WebAppAdapter($this->createContext());

            $this->assertFalse($GLOBALS['bootEventConfigLoadedAtRegistration']);
            $this->assertTrue(config()->has('app'));
        });
    }

    public function testWebAppAdapterSecondBootDoesNotRegisterAppHelperListenerAgain(): void
    {
        $this->withTemporaryAppHelper(function (): void {
            new WebAppAdapter($this->createContext());
            $this->assertCount(1, $GLOBALS['bootEventPayloads']);

            // Helper files load with require_once, so a new container does not get the listener again.
            new WebAppAdapter($this->createContext());
            $this->assertCount(1, $GLOBALS['bootEventPayloads']);
        });
    }

    private function withTemporaryAppHelper(callable $test): void
    {
        $dir = PROJECT_ROOT . DS . 'helpers';
        $createdDir = !is_dir($dir);
        $file = $dir . DS . uniqid('event_listener_helper_') . '.php';

        $helper = <<<'PHP'
<?php

$GLOBALS['bootEventConfigLoadedAtRegistration'] = config()->has('app');

event()->listen('boot.modules.before', function (array $payload): void {
    $GLOBALS['bootEventPayloads'][] = $payload;
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
            unset($GLOBALS['bootEventPayloads'], $GLOBALS['bootEventConfigLoadedAtRegistration']);
        }
    }
}
