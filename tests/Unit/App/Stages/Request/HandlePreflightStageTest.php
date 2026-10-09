<?php

namespace Quantum\Tests\Unit\App\Stages\Request;

use Quantum\App\Stages\Request\HandlePreflightStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Http\Enums\StatusCode;
use Quantum\App\Adapters\Web\RequestContext;
use Quantum\App\App;

class HandlePreflightStageTest extends AppTestCase
{
    private RequestContext $requestContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestContext = new RequestContext(App::getContext());
    }

    public function testHandlePreflightStageRespondsNoContentToOptionsRequest(): void
    {
        request()->create('OPTIONS', '/test/am/tests');

        (new HandlePreflightStage())->process($this->requestContext);

        $this->assertSame(response(), $this->requestContext->getResponse());
        $this->assertSame(StatusCode::NO_CONTENT, $this->requestContext->getResponse()->getStatusCode());
        $this->assertTrue($this->requestContext->isStopped());
    }

    public function testHandlePreflightStageIgnoresOtherMethods(): void
    {
        request()->create('GET', '/test/am/tests');

        (new HandlePreflightStage())->process($this->requestContext);

        $this->assertNull($this->requestContext->getResponse());
        $this->assertFalse($this->requestContext->isStopped());
    }
}
