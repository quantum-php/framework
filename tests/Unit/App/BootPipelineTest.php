<?php

namespace Quantum\Tests\Unit\App;

use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Quantum\App\Contracts\BootStageInterface;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\InitDebuggerStage;
use Quantum\App\Stages\Boot\LoadModulesStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\App\Stages\Boot\InitHttpStage;
use Quantum\Tests\Unit\AppTestCase;
use InvalidArgumentException;
use Quantum\App\BootPipeline;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use RuntimeException;

class BootPipelineTest extends AppTestCase
{
    public function setUp(): void
    {
        $this->context = $this->createContext();
    }

    public function tearDown(): void
    {
        $this->clearAppContext();
    }

    public function testPipelineRunsStagesInOrder(): void
    {
        $log = [];

        $stage1 = $this->createStage(function () use (&$log) {
            $log[] = 'first';
        });

        $stage2 = $this->createStage(function () use (&$log) {
            $log[] = 'second';
        });

        $stage3 = $this->createStage(function () use (&$log) {
            $log[] = 'third';
        });

        $pipeline = new BootPipeline([$stage1, $stage2, $stage3]);
        $pipeline->run(new AppContext('', new DiContainer()));

        $this->assertSame(['first', 'second', 'third'], $log);
    }

    public function testEmptyPipelineRunsWithoutError(): void
    {
        $pipeline = new BootPipeline([]);
        $pipeline->run(new AppContext('', new DiContainer()));

        $this->assertTrue(true);
    }

    public function testPipelinePassesContextToStages(): void
    {
        $receivedBaseDir = null;

        $stage = $this->createStage(function (AppContext $context) use (&$receivedBaseDir) {
            $receivedBaseDir = $context->getBaseDir();
        });

        $pipeline = new BootPipeline([$stage]);
        $pipeline->run(new AppContext('/test/dir', new DiContainer()));

        $this->assertSame('/test/dir', $receivedBaseDir);
    }

    public function testPipelinePropagatesException(): void
    {
        $stage = $this->createStage(function () {
            throw new RuntimeException('Stage failed');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stage failed');

        $pipeline = new BootPipeline([$stage]);
        $pipeline->run(new AppContext('', new DiContainer()));
    }

    public function testPipelineDispatchesEventsAroundStage(): void
    {
        $this->loadHelpers();

        $log = [];

        event()->listen('test.first.before', function () use (&$log): void {
            $log[] = 'before';
        });

        event()->listen('test.first.after', function () use (&$log): void {
            $log[] = 'after';
        });

        $pipeline = new BootPipeline([$this->createFirstEventStage(function () use (&$log): void {
            $log[] = 'process';
        })]);

        $pipeline->run($this->context);

        $this->assertSame(['before', 'process', 'after'], $log);
    }

    public function testPipelineDispatchesEventsInStageOrder(): void
    {
        $this->loadHelpers();

        $log = [];

        foreach (['test.first.before', 'test.first.after', 'test.second.before', 'test.second.after'] as $name) {
            event()->listen($name, function () use (&$log, $name): void {
                $log[] = $name;
            });
        }

        $pipeline = new BootPipeline([
            $this->createFirstEventStage(function () use (&$log): void {
                $log[] = 'process first';
            }),
            $this->createSecondEventStage(function () use (&$log): void {
                $log[] = 'process second';
            }),
        ]);

        $pipeline->run($this->context);

        $this->assertSame([
            'test.first.before',
            'process first',
            'test.first.after',
            'test.second.before',
            'process second',
            'test.second.after',
        ], $log);
    }

    public function testPipelinePassesContextInEventPayload(): void
    {
        $this->loadHelpers();

        $payloads = [];

        event()->listen('test.first.before', function (array $payload) use (&$payloads): void {
            $payloads[] = $payload;
        });

        event()->listen('test.first.after', function (array $payload) use (&$payloads): void {
            $payloads[] = $payload;
        });

        $pipeline = new BootPipeline([$this->createFirstEventStage(function (): void {
        })]);

        $pipeline->run($this->context);

        $this->assertCount(2, $payloads);
        $this->assertSame(['context'], array_keys($payloads[0]));
        $this->assertSame($this->context, $payloads[0]['context']);
        $this->assertSame($this->context, $payloads[1]['context']);
    }

    public function testPipelineDispatchesOnlyAfterEventWhenStageDeclaresOnlyAfter(): void
    {
        $this->loadHelpers();

        $log = [];

        event()->listen('test.after-only.after', function () use (&$log): void {
            $log[] = 'after';
        });

        $pipeline = new BootPipeline([$this->createAfterOnlyEventStage(function () use (&$log): void {
            $log[] = 'process';
        })]);

        $pipeline->run($this->context);

        $this->assertSame(['process', 'after'], $log);
    }

    public function testPipelineKeepsListenersAfterStageEvents(): void
    {
        $this->loadHelpers();

        $count = 0;

        event()->listen('test.first.before', function () use (&$count): void {
            $count++;
        });

        $pipeline = new BootPipeline([$this->createFirstEventStage(function (): void {
        })]);

        $pipeline->run($this->context);
        $pipeline->run($this->context);

        $this->assertSame(2, $count);
    }

    public function testPipelineStagesDeclareBootEventNames(): void
    {
        $this->assertSame('boot.environment.before', LoadEnvironmentStage::BEFORE);
        $this->assertSame('boot.environment.after', LoadEnvironmentStage::AFTER);

        $this->assertSame('boot.config.before', LoadAppConfigStage::BEFORE);
        $this->assertSame('boot.config.after', LoadAppConfigStage::AFTER);

        $this->assertSame('boot.error_handler.before', SetupErrorHandlerStage::BEFORE);
        $this->assertSame('boot.error_handler.after', SetupErrorHandlerStage::AFTER);

        $this->assertSame('boot.http.before', InitHttpStage::BEFORE);
        $this->assertSame('boot.http.after', InitHttpStage::AFTER);

        $this->assertSame('boot.modules.before', LoadModulesStage::BEFORE);
        $this->assertSame('boot.modules.after', LoadModulesStage::AFTER);
    }

    public function testLoadHelpersAndInitDebuggerStagesDeclareNoEvents(): void
    {
        foreach ([LoadHelpersStage::class, InitDebuggerStage::class] as $stage) {
            $this->assertFalse(defined($stage . '::BEFORE'), $stage . ' must not declare BEFORE');
            $this->assertFalse(defined($stage . '::AFTER'), $stage . ' must not declare AFTER');
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPipelineSkipsStageEventsWhileEventHelperIsNotLoaded(): void
    {
        $this->assertFalse(function_exists('event'));

        $processed = false;

        $pipeline = new BootPipeline([$this->createFirstEventStage(function () use (&$processed): void {
            $processed = true;
        })]);

        $pipeline->run($this->context);

        $this->assertTrue($processed);
    }

    public function testPipelineRejectsInvalidStage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All stages must implement');

        new BootPipeline([new \stdClass()]);
    }

    private function loadHelpers(): void
    {
        (new LoadHelpersStage())->process($this->context);
    }

    private function createStage(callable $callback): BootStageInterface
    {
        return new class ($callback) implements BootStageInterface {
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(AppContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }

    private function createFirstEventStage(callable $callback): BootStageInterface
    {
        return new class ($callback) implements BootStageInterface {
            public const BEFORE = 'test.first.before';
            public const AFTER = 'test.first.after';

            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(AppContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }

    private function createSecondEventStage(callable $callback): BootStageInterface
    {
        return new class ($callback) implements BootStageInterface {
            public const BEFORE = 'test.second.before';
            public const AFTER = 'test.second.after';

            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(AppContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }

    private function createAfterOnlyEventStage(callable $callback): BootStageInterface
    {
        return new class ($callback) implements BootStageInterface {
            public const AFTER = 'test.after-only.after';

            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(AppContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }
}
