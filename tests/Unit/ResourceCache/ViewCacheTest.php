<?php

namespace Quantum\Tests\Unit\ResourceCache;

use Quantum\ResourceCache\Exceptions\ResourceCacheException;
use Quantum\ResourceCache\ViewCache;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\MatchedRoute;
use Quantum\Router\Route;
use Quantum\Di\Di;

class ViewCacheDouble extends ViewCache
{
    private bool $forceMissingHtmlMin = false;

    public function simulateMissingHtmlMin(bool $state): void
    {
        $this->forceMissingHtmlMin = $state;
    }

    protected function htmlMinifierExists(): bool
    {
        if ($this->forceMissingHtmlMin) {
            return false;
        }

        return class_exists(\voku\helper\HtmlMin::class);
    }

}

class ViewCacheTest extends AppTestCase
{
    private \Quantum\ResourceCache\ViewCache $viewCache;

    private string $route = '/current-route';

    private string $content = <<<HEREDOC
    <div>
        <span>Test html content</span>span>
    </div>
HEREDOC;

    public function setUp(): void
    {
        parent::setUp();

        if (!Di::isRegistered(ViewCache::class)) {
            Di::register(ViewCache::class);
        }

        $this->viewCache = Di::get(ViewCache::class);
        $this->viewCache->setup();
    }

    public function tearDown(): void
    {
        $this->viewCache->delete($this->route);
        $this->viewCache->enableCaching(false);
        parent::tearDown();
    }

    public function testGetCachedResponse(): void
    {
        $this->viewCache->enableCaching(true);

        $this->viewCache->set($this->route, $this->content);

        $result = $this->viewCache->getCachedResponse($this->route);

        $this->assertSame(response(), $result);
        $this->assertEquals($this->content, $result->getContent());
    }

    public function testSetAndGetViewCache(): void
    {
        $this->viewCache->set($this->route, $this->content);

        $viewCache = $this->viewCache->get($this->route);

        $this->assertEquals($this->content, $viewCache);
    }

    public function testViewCacheContentMinification(): void
    {
        $this->viewCache->enableMinification(true);

        $this->viewCache->set($this->route, $this->content);

        $content = $this->viewCache->get($this->route);

        $minifiedContent = '<div><span>Test html content</span>span> </div>';

        $this->assertEquals($minifiedContent, $content);
    }

    public function testViewCacheMinificationMissingDependency(): void
    {
        $viewCache = new ViewCacheDouble();
        $viewCache->enableMinification(true);
        $viewCache->simulateMissingHtmlMin(true);

        $this->expectException(ResourceCacheException::class);

        $viewCache->set($this->route, '<div>test</div>');
    }

    public function testExistsViewCache(): void
    {
        $this->viewCache->set($this->route, $this->content);

        $this->assertTrue($this->viewCache->exists($this->route));
    }

    public function testDeleteViewCache(): void
    {
        $this->viewCache->set($this->route, $this->content);

        $this->assertTrue($this->viewCache->exists($this->route));

        $this->viewCache->delete($this->route);

        $this->assertFalse($this->viewCache->exists($this->route));
    }

    public function testGetNonExistentViewCache(): void
    {
        $viewCache = $this->viewCache->get('/non-existent-route');

        $this->assertNull($viewCache);
    }

    public function testViewCacheIsExpired(): void
    {
        $this->viewCache->setTtl(1);

        $this->viewCache->set($this->route, $this->content);

        sleep(2);

        $viewCache = $this->viewCache->get($this->route);

        $this->assertNull($viewCache);
    }

    public function testEnableDisableViewCache(): void
    {
        $this->assertFalse($this->viewCache->isEnabled());

        $this->viewCache->enableCaching(true);

        $this->assertTrue($this->viewCache->isEnabled());

        $this->viewCache->enableCaching(false);

        $this->assertFalse($this->viewCache->isEnabled());
    }

    public function testCacheDirIsTheViewsDirWhenThereIsNoModule(): void
    {
        $this->assertSame(
            base_dir() . DS . 'cache' . DS . 'views',
            $this->invokePrivateMethod($this->viewCache, 'getCacheDir')
        );
    }

    public function testCacheDirHasTheLowercaseModuleNameWhenThereIsAModule(): void
    {
        request()->setMatchedRoute(new MatchedRoute(
            (new Route(['GET'], '/current-route', 'TestController', 'tests'))->module('Test'),
            []
        ));

        $this->assertSame(
            base_dir() . DS . 'cache' . DS . 'views' . DS . 'test',
            $this->invokePrivateMethod($this->viewCache, 'getCacheDir')
        );
    }

    public function testViewCacheFollowsResourceCacheForRouteWithoutCacheSetting(): void
    {
        config()->set('resource_cache', true);

        $this->matchRoute();

        $this->assertTrue((new ViewCache())->isEnabled());
    }

    public function testViewCacheIsEnabledForCacheableRoute(): void
    {
        $this->matchRoute(true);

        $this->assertTrue((new ViewCache())->isEnabled());
    }

    public function testViewCacheIsDisabledForRouteNotCacheable(): void
    {
        config()->set('resource_cache', true);

        $this->matchRoute(false);

        $this->assertFalse((new ViewCache())->isEnabled());
    }

    public function testViewCacheUsesTheTtlOfCacheableRoute(): void
    {
        $this->matchRoute(true, 60);

        $viewCache = new ViewCache();
        $viewCache->setup();

        $this->assertSame(60, $this->getPrivateProperty($viewCache, 'ttl'));
    }

    public function testViewCacheUsesTheConfigTtlWhenRouteHasNone(): void
    {
        $this->matchRoute(true);

        $viewCache = new ViewCache();
        $viewCache->setup();

        $this->assertSame(300, $this->getPrivateProperty($viewCache, 'ttl'));
    }

    private function matchRoute(?bool $cacheEnabled = null, ?int $ttl = null): void
    {
        $route = new Route(['GET'], '/current-route', 'TestController', 'tests');

        if ($cacheEnabled !== null) {
            $route->cache($cacheEnabled, $ttl);
        }

        request()->setMatchedRoute(new MatchedRoute($route, []));
    }
}
