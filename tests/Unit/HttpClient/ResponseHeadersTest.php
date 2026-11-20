<?php

namespace Quantum\Tests\Unit\HttpClient;

use PHPUnit\Framework\Attributes\DataProvider;
use Quantum\HttpClient\ResponseHeaders;
use Quantum\Tests\Unit\AppTestCase;
use ReflectionMethod;

class ResponseHeadersTest extends AppTestCase
{
    public function testResponseHeadersProvidesCaseInsensitiveAccess(): void
    {
        $headers = new ResponseHeaders(['Content-Type' => 'application/json']);

        $this->assertSame('application/json', $headers['content-type']);
        $this->assertSame('application/json', $headers['CONTENT-TYPE']);
        $this->assertTrue(isset($headers['Content-Type']));
    }

    public function testResponseHeadersPreservesLatestOriginalKeyWhenIterating(): void
    {
        $headers = new ResponseHeaders(['Content-Type' => 'application/json']);
        $headers['content-type'] = 'text/plain';

        $this->assertSame(['content-type' => 'text/plain'], iterator_to_array($headers));
    }

    public function testResponseHeadersCanUnsetCaseInsensitively(): void
    {
        $headers = new ResponseHeaders(['Content-Type' => 'application/json']);

        unset($headers['content-type']);

        $this->assertCount(0, $headers);
        $this->assertNull($headers['Content-Type']);
    }

    #[DataProvider('interfaceMethodProvider')]
    public function testResponseHeadersDeclaresTheReturnTypeOfItsInterfaceMethods(string $method): void
    {
        $this->assertTrue((new ReflectionMethod(ResponseHeaders::class, $method))->hasReturnType());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function interfaceMethodProvider(): array
    {
        return [
            'offsetSet' => ['offsetSet'],
            'offsetExists' => ['offsetExists'],
            'offsetUnset' => ['offsetUnset'],
            'offsetGet' => ['offsetGet'],
            'count' => ['count'],
            'current' => ['current'],
            'next' => ['next'],
            'key' => ['key'],
            'valid' => ['valid'],
            'rewind' => ['rewind'],
        ];
    }
}
