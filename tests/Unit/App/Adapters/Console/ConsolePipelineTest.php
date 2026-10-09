<?php

namespace Quantum\Tests\Unit\App\Adapters\Console;

use Symfony\Component\Console\Output\BufferedOutput;
use Quantum\App\Contracts\ConsoleStageInterface;
use Quantum\App\Contracts\RequestStageInterface;
use Quantum\App\Contracts\BootStageInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Application;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Console\ConsoleContext;
use Quantum\App\Adapters\Console\ConsolePipeline;
use Quantum\App\Adapters\Web\RequestContext;
use InvalidArgumentException;
use Quantum\App\Enums\ExitCode;
use Quantum\App\AppContext;
use RuntimeException;

class ConsolePipelineTest extends AppTestCase
{
    private ConsoleContext $consoleContext;

    public function setUp(): void
    {
        $this->consoleContext = new ConsoleContext(
            $this->createContext(),
            new Application(),
            new ArrayInput([]),
            new BufferedOutput()
        );
    }

    public function tearDown(): void
    {
        $this->clearAppContext();
    }

    public function testPipelineRunsStagesInOrder(): void
    {
        $log = [];

        $pipeline = new ConsolePipeline([
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

        $pipeline->run($this->consoleContext);

        $this->assertSame(['first', 'second', 'third'], $log);
    }

    public function testEmptyPipelineRunsWithoutError(): void
    {
        $pipeline = new ConsolePipeline([]);

        $pipeline->run($this->consoleContext);

        $this->assertSame(ExitCode::SUCCESS, $this->consoleContext->getExitCode());
    }

    public function testPipelinePassesContextToStages(): void
    {
        $received = null;

        $pipeline = new ConsolePipeline([
            $this->createStage(function (ConsoleContext $context) use (&$received): void {
                $received = $context;
            }),
        ]);

        $pipeline->run($this->consoleContext);

        $this->assertSame($this->consoleContext, $received);
    }

    public function testPipelineCarriesExitCodeBetweenStages(): void
    {
        $seen = null;

        $pipeline = new ConsolePipeline([
            $this->createStage(function (ConsoleContext $context): void {
                $context->setExitCode(ExitCode::FAILURE);
            }),
            $this->createStage(function (ConsoleContext $context) use (&$seen): void {
                $seen = $context->getExitCode();
            }),
        ]);

        $pipeline->run($this->consoleContext);

        $this->assertSame(ExitCode::FAILURE, $seen);
        $this->assertSame(ExitCode::FAILURE, $this->consoleContext->getExitCode());
    }

    public function testPipelinePropagatesException(): void
    {
        $pipeline = new ConsolePipeline([
            $this->createStage(function (): void {
                throw new RuntimeException('Stage failed');
            }),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stage failed');

        $pipeline->run($this->consoleContext);
    }

    public function testPipelineRejectsInvalidStage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All stages must implement');

        new ConsolePipeline([new \stdClass()]);
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

        new ConsolePipeline([$bootStage]);
    }

    public function testPipelineRejectsRequestStage(): void
    {
        $requestStage = new class () implements RequestStageInterface {
            public function process(RequestContext $context): void
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All stages must implement');

        new ConsolePipeline([$requestStage]);
    }

    private function createStage(callable $callback): ConsoleStageInterface
    {
        return new class ($callback) implements ConsoleStageInterface {
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(ConsoleContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }
}
