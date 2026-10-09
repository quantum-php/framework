<?php

namespace Quantum\Tests\Unit\App\Adapters\Web;

use Quantum\App\Contracts\RequestStageInterface;
use Quantum\App\Contracts\BootStageInterface;
use Quantum\App\Adapters\Web\RequestPipeline;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Web\RequestContext;
use InvalidArgumentException;
use Quantum\App\AppContext;
use RuntimeException;

class RequestPipelineTest extends AppTestCase
{
    private RequestContext $requestContext;

    public function setUp(): void
    {
        $this->requestContext = new RequestContext($this->createContext());
    }

    public function tearDown(): void
    {
        $this->clearAppContext();
    }

    public function testPipelineRunsStagesInOrder(): void
    {
        $log = [];

        $pipeline = new RequestPipeline([
            $this->createStage(function () use (&$log): void {
                $log[] = 'first';
            }),
            $this->createStage(function () use (&$log): void {
                $log[] = 'second';
            }),
            $this->createStage(function () use (&$log): void {
                $log[] = 'third';
            }),
        ]);

        $pipeline->run($this->requestContext);

        $this->assertSame(['first', 'second', 'third'], $log);
    }

    public function testEmptyPipelineRunsWithoutError(): void
    {
        $pipeline = new RequestPipeline([]);

        $pipeline->run($this->requestContext);

        $this->assertFalse($this->requestContext->isStopped());
    }

    public function testPipelinePassesContextToStages(): void
    {
        $received = null;

        $pipeline = new RequestPipeline([
            $this->createStage(function (RequestContext $context) use (&$received): void {
                $received = $context;
            }),
        ]);

        $pipeline->run($this->requestContext);

        $this->assertSame($this->requestContext, $received);
    }

    public function testPipelinePropagatesException(): void
    {
        $pipeline = new RequestPipeline([
            $this->createStage(function (): void {
                throw new RuntimeException('Stage failed');
            }),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stage failed');

        $pipeline->run($this->requestContext);
    }

    public function testPipelineKeepsRunningStagesAfterContextIsStopped(): void
    {
        $log = [];

        $pipeline = new RequestPipeline([
            $this->createStage(function (RequestContext $context) use (&$log): void {
                $log[] = 'first';
                $context->stop();
            }),
            $this->createStage(function () use (&$log): void {
                $log[] = 'second';
            }),
        ]);

        $pipeline->run($this->requestContext);

        $this->assertSame(['first', 'second'], $log);
        $this->assertTrue($this->requestContext->isStopped());
    }

    public function testPipelineRejectsInvalidStage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All stages must implement');

        new RequestPipeline([new \stdClass()]);
    }

    public function testPipelineRejectsBootStage(): void
    {
        $bootStage = new class () implements BootStageInterface {
            public function process(AppContext $context): void
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All stages must implement');

        new RequestPipeline([$bootStage]);
    }

    private function createStage(callable $callback): RequestStageInterface
    {
        return new class ($callback) implements RequestStageInterface {
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(RequestContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }
}
