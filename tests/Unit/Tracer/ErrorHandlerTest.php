<?php

namespace Quantum\Tests\Unit\Tracer;

use Symfony\Component\Console\Output\BufferedOutput;
use PHPUnit\Framework\Attributes\DataProvider;
use Quantum\Tracer\WebExceptionRenderer;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Tracer\ErrorHandler;
use Quantum\App\Enums\AppType;
use Quantum\Logger\Logger;
use ReflectionException;
use ReflectionFunction;
use ErrorException;
use ParseError;
use Exception;
use Closure;
use Mockery;

class ErrorHandlerTest extends AppTestCase
{
    private ErrorHandler $errorHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->errorHandler = new ErrorHandler();
    }

    public function testSetupRegistersErrorHandler(): void
    {
        $logger = Mockery::mock(Logger::class);

        $this->errorHandler->setup($logger);

        try {
            $currentHandler = set_error_handler(function () {
            });
            restore_error_handler();

            $this->assertNotNull($currentHandler);
            $this->assertInstanceOf(Closure::class, $currentHandler);

            $reflection = new ReflectionFunction($currentHandler);

            $this->assertInstanceOf(ErrorHandler::class, $reflection->getClosureThis());
            $this->assertEquals('handleError', $reflection->getName());
        } finally {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testSetupRegistersExceptionHandler(): void
    {
        $logger = Mockery::mock(Logger::class);

        $this->errorHandler->setup($logger);

        try {
            $currentHandler = set_exception_handler(function () {
            });
            restore_exception_handler();

            $this->assertNotNull($currentHandler);
            $this->assertInstanceOf(Closure::class, $currentHandler);

            $reflection = new ReflectionFunction($currentHandler);

            $this->assertInstanceOf(ErrorHandler::class, $reflection->getClosureThis());
            $this->assertEquals('handleException', $reflection->getName());
        } finally {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testHandleErrorThrowsErrorException(): void
    {
        $oldLevel = error_reporting(E_ALL);

        try {
            $this->errorHandler->handleError(E_WARNING, 'Test error', __FILE__, __LINE__);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $e) {
            $this->assertEquals('Test error', $e->getMessage());
            $this->assertEquals(E_WARNING, $e->getSeverity());
        } finally {
            error_reporting($oldLevel);
        }
    }

    public function testHandleErrorReturnsFalseForSuppressedErrors(): void
    {
        $oldLevel = error_reporting(0);

        try {
            $result = $this->errorHandler->handleError(E_NOTICE, 'Suppressed', __FILE__, __LINE__);
            $this->assertFalse($result);
        } finally {
            error_reporting($oldLevel);
        }
    }

    #[DataProvider('deprecationSeverityProvider')]
    public function testHandleErrorLogsADeprecationInsteadOfThrowing(int $severity): void
    {
        $logged = $this->captureWarnings();

        $oldLevel = error_reporting(E_ALL);

        try {
            $result = $this->errorHandler->handleError($severity, 'Feature is deprecated', '/app/file.php', 12);

            $this->assertTrue($result);
            $this->assertSame(['Feature is deprecated in /app/file.php:12'], $logged());
        } finally {
            error_reporting($oldLevel);
        }
    }

    #[DataProvider('deprecationSeverityProvider')]
    public function testHandleErrorIgnoresADeprecationWhenThereIsNoLogger(int $severity): void
    {
        $this->setPrivateProperty($this->errorHandler, 'logger', null);

        $oldLevel = error_reporting(E_ALL);

        try {
            $this->assertTrue($this->errorHandler->handleError($severity, 'Feature is deprecated', __FILE__, __LINE__));
        } finally {
            error_reporting($oldLevel);
        }
    }

    public function testHandleErrorLeavesAnUnreportedDeprecationToPhp(): void
    {
        $logged = $this->captureWarnings();

        $oldLevel = error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            $this->assertFalse($this->errorHandler->handleError(E_DEPRECATED, 'Feature is deprecated', __FILE__, __LINE__));
            $this->assertSame([], $logged());
        } finally {
            error_reporting($oldLevel);
        }
    }

    public function testHandleExceptionCliWritesExceptionMessage(): void
    {
        $this->useAppType(AppType::CONSOLE);

        config()->set('app.debug', false);

        $output = new BufferedOutput();
        $this->errorHandler->setCliOutput($output);

        $this->errorHandler->handleException(new Exception('CLI failure'));

        $this->assertStringContainsString('CLI failure', $output->fetch());
    }

    public function testHandleExceptionCliInDebugModeWritesVerboseDiagnostics(): void
    {
        $this->useAppType(AppType::CONSOLE);

        config()->set('app.debug', true);

        $output = new BufferedOutput();
        $this->errorHandler->setCliOutput($output);

        $this->errorHandler->handleException(new Exception('CLI debug failure'));

        $display = $output->fetch();
        $this->assertStringContainsString(Exception::class . ': CLI debug failure', $display);
        $this->assertStringContainsString('In ', $display);
        $this->assertStringContainsString(__FILE__, $display);
    }

    public function testHandleExceptionInWebAppRendersTheErrorPage(): void
    {
        config()->set('app.debug', true);

        $output = new BufferedOutput();
        $this->errorHandler->setCliOutput($output);

        ob_start();
        $this->errorHandler->handleException(new Exception('Web app failure'));
        ob_get_clean();

        $this->assertStringContainsString('Error :: Web app failure', response()->getContent());
        $this->assertSame('', $output->fetch());
    }

    public function testHandleWebExceptionInDebugModeRendersTraceView(): void
    {
        config()->set('app.debug', true);

        $exception = new Exception('Web debug failure');

        ob_start();
        $this->invokePrivateMethod($this->errorHandler, 'handleWebException', [$exception]);
        ob_get_clean();

        $content = response()->getContent();
        $this->assertStringContainsString('TRACE VIEW', $content);
        $this->assertStringContainsString('Error :: Web debug failure', $content);
    }

    public function testHandleWebExceptionInProductionModeLogsAndRenders500(): void
    {
        config()->set('app.debug', false);

        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('warning')
            ->once()
            ->with('Web production warning', Mockery::on(function (array $context): bool {
                return array_key_exists('trace', $context) && is_string($context['trace']);
            }));

        $this->setPrivateProperty($this->errorHandler, 'logger', $logger);

        $exception = new ErrorException('Web production warning', 0, E_WARNING, __FILE__, __LINE__);

        ob_start();
        $this->invokePrivateMethod($this->errorHandler, 'handleWebException', [$exception]);
        ob_get_clean();

        $content = response()->getContent();
        $this->assertStringContainsString('ERROR 500 VIEW', $content);
    }

    public function testHandleWebExceptionFallsBackWhenRendererThrows(): void
    {
        config()->set('app.debug', true);

        $renderer = Mockery::mock(WebExceptionRenderer::class);
        $renderer->shouldReceive('render')
            ->once()
            ->andThrow(new Exception('render failed'));

        $handler = new ErrorHandler(null, null, $renderer);

        ob_start();
        $this->invokePrivateMethod($handler, 'handleWebException', [new Exception('boom')]);
        ob_get_clean();

        $this->assertSame('Internal Server Error', response()->getContent());
        $this->assertSame(500, response()->getStatusCode());
        $this->assertStringStartsWith('text/html', (string) response()->getHeader('Content-Type'));
    }

    public function testLogErrorReturnsEarlyWhenLoggerIsNull(): void
    {
        $this->setPrivateProperty($this->errorHandler, 'logger', null);

        $this->invokePrivateMethod($this->errorHandler, 'logError', [new Exception('No logger'), 'error']);

        $this->assertTrue(true);
    }

    public function testLogErrorFallsBackToErrorMethodForUnknownLevel(): void
    {
        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('error')
            ->once()
            ->with('Unknown level message', Mockery::type('array'));

        $this->setPrivateProperty($this->errorHandler, 'logger', $logger);

        $this->invokePrivateMethod($this->errorHandler, 'logError', [new Exception('Unknown level message'), 'not_existing_level']);

        $this->assertTrue(true);
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function deprecationSeverityProvider(): array
    {
        return [
            'deprecated' => [E_DEPRECATED],
            'user-deprecated' => [E_USER_DEPRECATED],
        ];
    }

    #[DataProvider('errorTypeProvider')]
    public function testGetErrorTypeMappings(\Throwable $throwable, string $expected): void
    {
        $type = $this->invokePrivateMethod($this->errorHandler, 'getErrorType', [$throwable]);

        $this->assertSame($expected, $type);
    }

    /**
     * @return array<string, array{0: \Throwable, 1: string}>
     */
    public static function errorTypeProvider(): array
    {
        return [
            'mapped-error-exception' => [new ErrorException('x', 0, E_WARNING), 'warning'],
            'unmapped-error-exception' => [new ErrorException('x', 0, E_USER_DEPRECATED), 'error'],
            'parse-error' => [new ParseError('bad syntax'), 'critical'],
            'reflection-exception' => [new ReflectionException('reflection failed'), 'warning'],
            'generic-exception' => [new Exception('unknown'), 'error'],
        ];
    }

    /**
     * Gives the handler a logger that records its warnings, and returns a function that lists them
     * @return callable(): array<int, string>
     */
    private function captureWarnings(): callable
    {
        $logged = [];

        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('warning')->andReturnUsing(function (string $message) use (&$logged): void {
            $logged[] = $message;
        });

        $this->setPrivateProperty($this->errorHandler, 'logger', $logger);

        return function () use (&$logged): array {
            return $logged;
        };
    }

}
