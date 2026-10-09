<?php

namespace Quantum\Tests\Unit\App\Adapters\Web;

use Quantum\ResourceCache\ViewCache;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\MatchedRoute;
use Quantum\App\Adapters\Web\RequestContext;
use Quantum\App\AppContext;
use Quantum\Router\Route;
use Quantum\App\App;
use Quantum\Di\Di;

class RequestContextTest extends AppTestCase
{
    private AppContext $appContext;

    private RequestContext $requestContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->appContext = App::getContext();
        $this->requestContext = new RequestContext($this->appContext);
    }

    public function testRequestContextExposesAppContext(): void
    {
        $this->assertSame($this->appContext, $this->requestContext->getAppContext());
    }

    public function testRequestContextStoresMatchedRoute(): void
    {
        $this->assertNull($this->requestContext->getMatchedRoute());

        $matchedRoute = new MatchedRoute(new Route(['GET'], '/test/am/tests', 'TestController', 'tests'), []);

        $this->requestContext->setMatchedRoute($matchedRoute);

        $this->assertSame($matchedRoute, $this->requestContext->getMatchedRoute());
    }

    public function testRequestContextStoresResponse(): void
    {
        $this->assertNull($this->requestContext->getResponse());

        $this->requestContext->setResponse(response());

        $this->assertSame(response(), $this->requestContext->getResponse());
    }

    public function testRequestContextStoresViewCache(): void
    {
        $this->assertNull($this->requestContext->getViewCache());

        if (!Di::isRegistered(ViewCache::class)) {
            Di::register(ViewCache::class);
        }

        $viewCache = Di::get(ViewCache::class);

        $this->requestContext->setViewCache($viewCache);

        $this->assertSame($viewCache, $this->requestContext->getViewCache());
    }

    public function testRequestContextIsNotStoppedByDefault(): void
    {
        $this->assertFalse($this->requestContext->isStopped());
    }

    public function testRequestContextStopMarksContextStopped(): void
    {
        $this->requestContext->stop();

        $this->assertTrue($this->requestContext->isStopped());
    }
}
