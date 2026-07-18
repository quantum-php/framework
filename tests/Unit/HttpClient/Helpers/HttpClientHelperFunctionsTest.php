<?php

namespace Quantum\Tests\Unit\HttpClient\Helpers;

use Quantum\HttpClient\Adapters\MultiCurlAdapter;
use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\HttpClient\HttpClient;

class HttpClientHelperFunctionsTest extends AppTestCase
{
    public function testHttpRequestHelperCreatesSingleRequest(): void
    {
        $httpClient1 = httpRequest('https://example.com');
        $httpClient2 = httpRequest('https://example.org');

        $this->assertInstanceOf(HttpClient::class, $httpClient1);
        $this->assertInstanceOf(CurlAdapter::class, $httpClient1->getAdapter());
        $this->assertNotSame($httpClient1, $httpClient2);
    }

    public function testHttpMultiRequestHelperCreatesMultiRequest(): void
    {
        $httpClient1 = httpMultiRequest();
        $httpClient2 = httpMultiRequest();

        $this->assertInstanceOf(HttpClient::class, $httpClient1);
        $this->assertTrue($httpClient1->isMultiRequest());
        $this->assertInstanceOf(MultiCurlAdapter::class, $httpClient1->getAdapter());
        $this->assertNotSame($httpClient1, $httpClient2);
    }

    public function testHttpAsyncMultiRequestHelperCreatesAsyncMultiRequest(): void
    {
        $success = static function (): void {
        };

        $error = static function (): void {
        };

        $httpClient1 = httpAsyncMultiRequest($success, $error);
        $httpClient2 = httpAsyncMultiRequest($success, $error);

        $this->assertInstanceOf(HttpClient::class, $httpClient1);
        $this->assertTrue($httpClient1->isMultiRequest());
        $this->assertInstanceOf(MultiCurlAdapter::class, $httpClient1->getAdapter());
        $this->assertNotSame($httpClient1, $httpClient2);
    }
}
