<?php

namespace Quantum\Tests\Unit\HttpClient\Adapters;

use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\HttpClient\ResponseHeaders;
use Quantum\Tests\Unit\AppTestCase;
use CurlHandle;
use Mockery;

class CurlAdapterTest extends AppTestCase
{
    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testCurlAdapterDelegatesRequestMethods(): void
    {
        $adapter = new CurlAdapter();

        $this->assertSame($adapter, $adapter->setUrl('https://example.com'));
        $this->assertSame($adapter, $adapter->setHeader('Accept', 'application/json'));
        $this->assertSame($adapter, $adapter->setHeaders(['X-Test' => 'yes']));
        $this->assertSame($adapter, $adapter->setOpt(CURLOPT_TIMEOUT, 10));
        $this->assertSame($adapter, $adapter->setOpts([CURLOPT_CONNECTTIMEOUT => 5]));
        $this->assertSame('a=1', $adapter->buildPostData(['a' => 1]));
        $this->assertSame('https://example.com', $adapter->getUrl());
    }

    public function testCurlAdapterBuildsJsonPostData(): void
    {
        $adapter = new CurlAdapter();
        $adapter->setHeader('Content-Type', 'application/json');

        $this->assertSame('{"a":1}', $adapter->buildPostData(['a' => 1]));
    }

    public function testCurlAdapterPreservesMultipartPostData(): void
    {
        $adapter = new CurlAdapter();
        $adapter->setHeader('Content-Type', 'multipart/form-data');
        $data = ['name' => 'avatar'];

        $this->assertSame($data, $adapter->buildPostData($data));
    }

    public function testCurlAdapterPreservesCurlFilePostData(): void
    {
        $adapter = new CurlAdapter();
        $file = new \CURLFile(__FILE__);
        $data = ['file' => $file];

        $this->assertSame($data, $adapter->buildPostData($data));
    }

    public function testCurlAdapterGeneratesNativeRequestIds(): void
    {
        $adapter1 = new CurlAdapter();
        $adapter2 = new CurlAdapter();

        $this->assertNotSame($adapter1->getId(), $adapter2->getId());
    }

    public function testCurlAdapterExposesNativeHandle(): void
    {
        $adapter = new CurlAdapter();

        $this->assertInstanceOf(CurlHandle::class, $adapter->getHandle());
    }

    public function testCurlAdapterParsesNativeResponseHeaders(): void
    {
        $adapter = new CurlAdapter();

        $headers = $this->invokePrivateMethod($adapter, 'parseResponseHeaders', [
            "HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\nContent-Type: application/json\r\nMalformed Header\r\nX-Test: one\r\nX-Test: two\r\n\r\n",
        ]);

        $this->assertInstanceOf(ResponseHeaders::class, $headers);
        $this->assertSame('HTTP/1.1 200 OK', $headers['status-line']);
        $this->assertSame('application/json', $headers['content-type']);
        $this->assertSame('one,two', $headers['x-test']);
    }

    public function testCurlAdapterReturnsEmptyHeadersWhenNoHttpHeaderBlockExists(): void
    {
        $adapter = new CurlAdapter();

        $headers = $this->invokePrivateMethod($adapter, 'parseResponseHeaders', ['']);

        $this->assertCount(0, $headers);
    }

    public function testCurlAdapterParsesNativeCookieHeader(): void
    {
        $adapter = new CurlAdapter();

        $this->invokePrivateMethod($adapter, 'parseCookieHeader', ['Set-Cookie: sid=abc%20123; Path=/; HttpOnly']);

        $this->assertSame(['sid' => 'abc 123'], $adapter->getResponseCookies());
    }

    public function testCurlAdapterParsesEmptyNativeCookieValue(): void
    {
        $adapter = new CurlAdapter();

        $this->invokePrivateMethod($adapter, 'parseCookieHeader', ['Set-Cookie: sid=abc123; Path=/; HttpOnly']);
        $this->invokePrivateMethod($adapter, 'parseCookieHeader', ['Set-Cookie: sid=; Max-Age=0; Path=/']);

        $this->assertSame(['sid' => ''], $adapter->getResponseCookies());
    }

    public function testCurlAdapterParsesJsonResponse(): void
    {
        $adapter = new CurlAdapter();
        $headers = $this->invokePrivateMethod($adapter, 'parseResponseHeaders', [
            "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n",
        ]);
        $this->setPrivateProperty($adapter, 'responseHeaders', $headers);

        $response = $this->invokePrivateMethod($adapter, 'parseResponse', ['{"ok":true}']);

        $this->assertTrue($response->ok);
    }

    public function testCurlAdapterParsesXmlResponse(): void
    {
        $adapter = new CurlAdapter();
        $headers = $this->invokePrivateMethod($adapter, 'parseResponseHeaders', [
            "HTTP/1.1 200 OK\r\nContent-Type: application/xml\r\n\r\n",
        ]);
        $this->setPrivateProperty($adapter, 'responseHeaders', $headers);

        $response = $this->invokePrivateMethod($adapter, 'parseResponse', ['<root><ok>yes</ok></root>']);

        $this->assertSame('yes', (string) $response->ok);
    }

    public function testCurlAdapterReturnsRawResponseWhenXmlParsingFails(): void
    {
        $adapter = new CurlAdapter();
        $headers = $this->invokePrivateMethod($adapter, 'parseResponseHeaders', [
            "HTTP/1.1 200 OK\r\nContent-Type: application/xml\r\n\r\n",
        ]);
        $this->setPrivateProperty($adapter, 'responseHeaders', $headers);

        $previousUseInternalErrors = libxml_use_internal_errors(false);

        try {
            $response = $this->invokePrivateMethod($adapter, 'parseResponse', ['<root>']);

            $this->assertSame('<root>', $response);
            $this->assertFalse(libxml_use_internal_errors());
        } finally {
            libxml_use_internal_errors($previousUseInternalErrors);
        }
    }

    public function testCurlAdapterDecodesGzipResponse(): void
    {
        $adapter = new CurlAdapter();
        $headers = $this->invokePrivateMethod($adapter, 'parseResponseHeaders', [
            "HTTP/1.1 200 OK\r\nContent-Encoding: gzip\r\n\r\n",
        ]);
        $this->setPrivateProperty($adapter, 'responseHeaders', $headers);

        $response = $this->invokePrivateMethod($adapter, 'parseResponse', [gzencode('compressed response')]);

        $this->assertSame('compressed response', $response);
    }

    public function testCurlAdapterParsesGzippedJsonResponse(): void
    {
        $adapter = new CurlAdapter();
        $headers = $this->invokePrivateMethod($adapter, 'parseResponseHeaders', [
            "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Encoding: GZip\r\n\r\n",
        ]);
        $this->setPrivateProperty($adapter, 'responseHeaders', $headers);

        $response = $this->invokePrivateMethod($adapter, 'parseResponse', [gzencode('{"ok":true}')]);

        $this->assertTrue($response->ok);
    }

    public function testCurlAdapterPassesZeroInfoOptionToNativeCurl(): void
    {
        $adapter = new CurlAdapter();

        $this->assertFalse($adapter->getInfo(0));
    }

    public function testCurlAdapterStoresNativeHttpErrorState(): void
    {
        $adapter = new CurlAdapter();
        $headers = $this->invokePrivateMethod($adapter, 'parseResponseHeaders', [
            "HTTP/1.1 404 Not Found\r\nContent-Type: text/plain\r\n\r\n",
        ]);

        $this->setPrivateProperty($adapter, 'responseHeaders', $headers);
        $this->setPrivateProperty($adapter, 'error', true);
        $this->setPrivateProperty($adapter, 'errorCode', 404);
        $this->setPrivateProperty($adapter, 'errorMessage', $headers['Status-Line']);

        $this->assertTrue($adapter->isError());
        $this->assertSame(404, $adapter->getErrorCode());
        $this->assertSame('HTTP/1.1 404 Not Found', $adapter->getErrorMessage());
    }

    public function testCurlAdapterStoresNativeCurlErrorState(): void
    {
        $adapter = new CurlAdapter();

        $adapter
            ->setUrl($this->fileUrl(PROJECT_ROOT . DS . 'missing-curl-fixture.txt'))
            ->start();

        $this->assertTrue($adapter->isError());
        $this->assertSame(CURLE_FILE_COULDNT_READ_FILE, $adapter->getErrorCode());
        $this->assertNotEmpty($adapter->getErrorMessage());
    }

    public function testCurlAdapterKeepsNativeErrorMessageNullOnSuccess(): void
    {
        $adapter = new CurlAdapter();
        $fixturePath = PROJECT_ROOT . DS . 'app.conf';

        $adapter
            ->setUrl($this->fileUrl($fixturePath))
            ->start();

        $this->assertFalse($adapter->isError());
        $this->assertSame(0, $adapter->getErrorCode());
        $this->assertNull($adapter->getErrorMessage());
        $this->assertSame(file_get_contents($fixturePath), $adapter->getResponse());
    }

    public function testCurlAdapterSupportsAndCallsNativeFacadeMethods(): void
    {
        $adapter = new CurlAdapter();

        $this->assertTrue($adapter->supportsMethod('setHeaders'));
        $this->assertFalse($adapter->supportsMethod('setTimeout'));
        $this->assertSame($adapter, $adapter->callMethod('setHeaders', [['Accept' => 'application/json']]));
        $this->assertNull($adapter->callMethod('setTimeout', []));
    }

    private function fileUrl(string $path): string
    {
        return 'file:///' . str_replace('\\', '/', $path);
    }
}
