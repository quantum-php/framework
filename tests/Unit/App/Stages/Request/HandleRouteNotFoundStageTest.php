<?php

namespace Quantum\Tests\Unit\App\Stages\Request;

use Quantum\App\Stages\Request\HandleRouteNotFoundStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Http\Enums\ContentType;
use Quantum\Http\Enums\StatusCode;
use Quantum\App\Adapters\Web\RequestContext;
use Quantum\Router\MatchedRoute;
use Quantum\Router\Route;
use Quantum\App\App;

class HandleRouteNotFoundStageTest extends AppTestCase
{
    private RequestContext $requestContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestContext = new RequestContext(App::getContext());
    }

    public function testHandleRouteNotFoundStageRespondsNotFoundWhenNoRouteMatched(): void
    {
        request()->create('GET', '/non-existing-uri');

        (new HandleRouteNotFoundStage())->process($this->requestContext);

        $this->assertSame(response(), $this->requestContext->getResponse());
        $this->assertSame(StatusCode::NOT_FOUND, $this->requestContext->getResponse()->getStatusCode());
        $this->assertSame(ContentType::HTML, $this->requestContext->getResponse()->getContentType());
        $this->assertTrue($this->requestContext->isStopped());
    }

    public function testHandleRouteNotFoundStageRespondsWithJsonForJsonRequests(): void
    {
        request()->create('GET', '/non-existing-uri', [], ['Accept' => 'application/json']);

        (new HandleRouteNotFoundStage())->process($this->requestContext);

        $this->assertSame(StatusCode::NOT_FOUND, $this->requestContext->getResponse()->getStatusCode());
        $this->assertSame(ContentType::JSON, $this->requestContext->getResponse()->getContentType());
        $this->assertStringContainsString('Page not found', $this->requestContext->getResponse()->getContent());
        $this->assertTrue($this->requestContext->isStopped());
    }

    public function testHandleRouteNotFoundStageIgnoresMatchedRoute(): void
    {
        $this->requestContext->setMatchedRoute(
            new MatchedRoute(new Route(['GET'], '/test/am/tests', 'TestController', 'tests'), [])
        );

        (new HandleRouteNotFoundStage())->process($this->requestContext);

        $this->assertNull($this->requestContext->getResponse());
        $this->assertFalse($this->requestContext->isStopped());
    }

    public function testHandleRouteNotFoundStageSkipsWhenContextIsStopped(): void
    {
        request()->create('GET', '/non-existing-uri');

        $this->requestContext->stop();

        (new HandleRouteNotFoundStage())->process($this->requestContext);

        $this->assertNull($this->requestContext->getResponse());
    }
}
