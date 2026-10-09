<?php

namespace Quantum\Tests\Unit\App\Stages\Request;

use Quantum\App\Stages\Request\SendResponseStage;
use Quantum\Http\Exceptions\HttpException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Web\RequestContext;
use Quantum\Router\MatchedRoute;
use Quantum\Router\Route;
use Quantum\Http\Response;
use Quantum\App\App;
use RuntimeException;

class SendResponseStageTest extends AppTestCase
{
    private RequestContext $requestContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestContext = new RequestContext(App::getContext());
    }

    public function testSendResponseStageSendsTheResponse(): void
    {
        $this->requestContext->setResponse(response()->html('<p>hello</p>'));

        ob_start();
        (new SendResponseStage())->process($this->requestContext);
        $output = ob_get_clean();

        $this->assertSame('<p>hello</p>', $output);
    }

    public function testSendResponseStageAppliesCorsHeaders(): void
    {
        $this->assertFalse(config()->has('cors'));

        $this->requestContext->setResponse(response()->html(''));

        ob_start();
        (new SendResponseStage())->process($this->requestContext);
        ob_end_clean();

        $this->assertTrue(config()->has('cors'));
        $this->assertSame('*', response()->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('*', response()->getHeader('Access-Control-Allow-Methods'));
    }

    public function testSendResponseStageCleansUpRequestAfterSending(): void
    {
        $this->prepareMatchedRequest();

        $this->requestContext->setResponse(response()->html(''));

        ob_start();
        (new SendResponseStage())->process($this->requestContext);
        ob_end_clean();

        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    public function testSendResponseStageCleansUpRequestWhenSendFails(): void
    {
        $this->prepareMatchedRequest();

        response()->setHeader('X-Test', '1');
        response()->json(['foo' => 'bar']);

        $this->requestContext->setResponse(new class () extends Response {
            public function send(): void
            {
                throw new HttpException('boom');
            }
        });

        try {
            (new SendResponseStage())->process($this->requestContext);
            $this->fail('Expected response sending to fail.');
        } catch (HttpException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
        $this->assertSame(['foo' => 'bar'], response()->all());
        $this->assertSame('1', response()->getHeader('X-Test'));
        $this->assertSame(200, response()->getStatusCode());
    }

    public function testSendResponseStageFailsAndCleansUpWithoutResponse(): void
    {
        $this->prepareMatchedRequest();

        try {
            (new SendResponseStage())->process($this->requestContext);
            $this->fail('Expected the stage to fail without a response.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The request has no response to send.', $exception->getMessage());
        }

        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    private function prepareMatchedRequest(): void
    {
        request()->create('GET', '/test/am/tests');

        request()->setMatchedRoute(new MatchedRoute(
            new Route(['GET'], '/test/am/tests', 'TestController', 'tests'),
            []
        ));
    }
}
