<?php

namespace Quantum\Tests\Unit\App\Stages\Request;

use Quantum\App\Stages\Request\ResolveRouteStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Web\RequestContext;
use Quantum\App\App;

class ResolveRouteStageTest extends AppTestCase
{
    private RequestContext $requestContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestContext = new RequestContext(App::getContext());
    }

    public function testResolveRouteStageStoresMatchedRoute(): void
    {
        request()->create('GET', '/test/am/tests');

        (new ResolveRouteStage())->process($this->requestContext);

        $this->assertNotNull($this->requestContext->getMatchedRoute());
        $this->assertSame('tests', $this->requestContext->getMatchedRoute()->getRoute()->getAction());
        $this->assertSame($this->requestContext->getMatchedRoute(), request()->getMatchedRoute());
        $this->assertFalse($this->requestContext->isStopped());
    }

    public function testResolveRouteStageLeavesContextEmptyWhenNoRouteMatches(): void
    {
        request()->create('GET', '/non-existing-uri');

        (new ResolveRouteStage())->process($this->requestContext);

        $this->assertNull($this->requestContext->getMatchedRoute());
        $this->assertNull(request()->getMatchedRoute());
        $this->assertFalse($this->requestContext->isStopped());
    }

    public function testResolveRouteStageSkipsWhenContextIsStopped(): void
    {
        request()->create('GET', '/test/am/tests');

        $this->requestContext->stop();

        (new ResolveRouteStage())->process($this->requestContext);

        $this->assertNull($this->requestContext->getMatchedRoute());
        $this->assertNull(request()->getMatchedRoute());
    }
}
