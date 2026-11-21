<?php

namespace Quantum\Tests\Unit\Tracer;

use PHPUnit\Framework\Attributes\DataProvider;
use Quantum\Tracer\ExceptionSeverityResolver;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ErrorException;
use ParseError;
use Exception;

class ExceptionSeverityResolverTest extends TestCase
{
    private ExceptionSeverityResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new ExceptionSeverityResolver();
    }

    #[DataProvider('mappingProvider')]
    public function testResolveMappings(\Throwable $throwable, string $expected): void
    {
        $this->assertSame($expected, $this->resolver->resolve($throwable));
    }

    /**
     * @return array<string, array{0: \Throwable, 1: string}>
     */
    public static function mappingProvider(): array
    {
        return [
            'mapped-error-exception' => [new ErrorException('x', 0, E_WARNING), 'warning'],
            'unmapped-error-exception' => [new ErrorException('x', 0, E_USER_DEPRECATED), 'error'],
            'parse-error' => [new ParseError('bad syntax'), 'critical'],
            'reflection-exception' => [new ReflectionException('reflection failed'), 'warning'],
            'generic-exception' => [new Exception('unknown'), 'error'],
        ];
    }
}
