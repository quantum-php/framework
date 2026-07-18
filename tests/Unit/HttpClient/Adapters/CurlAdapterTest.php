<?php

namespace Quantum\Tests\Unit\HttpClient\Adapters;

use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Curl\CaseInsensitiveArray;
use Curl\Curl;
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

    public function testCurlAdapterParsesNativeResponseHeaders(): void
    {
        $adapter = new CurlAdapter();

        $headers = $this->invokePrivateMethod($adapter, 'parseResponseHeaders', [
            "HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\nContent-Type: application/json\r\nMalformed Header\r\nX-Test: one\r\nX-Test: two\r\n\r\n",
        ]);

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

        $response = $this->invokePrivateMethod($adapter, 'parseResponse', ['<root>']);

        $this->assertSame('<root>', $response);
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

    public function testCurlAdapterKeepsInjectedVendorClientAsTransitionBridge(): void
    {
        $headers = new CaseInsensitiveArray(['Content-Type' => 'application/json']);
        $response = (object) ['ok' => true];

        $curl = Mockery::mock(Curl::class);
        $curl->shouldReceive('setUrl')->once()->with('https://example.com');
        $curl->shouldReceive('setHeader')->once()->with('Accept', 'application/json');
        $curl->shouldReceive('setHeaders')->once()->with(['X-Test' => 'yes']);
        $curl->shouldReceive('setOpt')->once()->with(CURLOPT_TIMEOUT, 10);
        $curl->shouldReceive('buildPostData')->once()->with(['a' => 1])->andReturn('payload');
        $curl->shouldReceive('exec')->once();
        $curl->shouldReceive('getId')->once()->andReturn(7);
        $curl->shouldReceive('isError')->once()->andReturn(false);
        $curl->shouldReceive('getErrorCode')->once()->andReturn(0);
        $curl->shouldReceive('getErrorMessage')->once()->andReturn(null);
        $curl->shouldReceive('getResponseHeaders')->once()->andReturn($headers);
        $curl->shouldReceive('getResponseCookies')->once()->andReturn(['sid' => 'abc']);
        $curl->shouldReceive('getResponse')->once()->andReturn($response);
        $curl->shouldReceive('getInfo')->with(CURLINFO_HTTP_CODE)->once()->andReturn(200);
        $adapter = new CurlAdapter($curl);
        $adapter
            ->setUrl('https://example.com')
            ->setHeader('Accept', 'application/json')
            ->setHeaders(['X-Test' => 'yes'])
            ->setOpt(CURLOPT_TIMEOUT, 10)
            ->start();

        $this->assertSame('payload', $adapter->buildPostData(['a' => 1]));
        $this->assertSame(7, $adapter->getId());
        $this->assertFalse($adapter->isError());
        $this->assertSame(0, $adapter->getErrorCode());
        $this->assertNull($adapter->getErrorMessage());
        $this->assertSame($headers, $adapter->getResponseHeaders());
        $this->assertSame(['sid' => 'abc'], $adapter->getResponseCookies());
        $this->assertSame($response, $adapter->getResponse());
        $this->assertSame(200, $adapter->getInfo(CURLINFO_HTTP_CODE));
        $this->assertSame('https://example.com', $adapter->getUrl());
    }

    public function testCurlAdapterPassesZeroInfoOption(): void
    {
        $curl = Mockery::mock(Curl::class);
        $curl->shouldReceive('getInfo')->with(0)->once()->andReturn('zero');

        $adapter = new CurlAdapter($curl);

        $this->assertSame('zero', $adapter->getInfo(0));
    }

    public function testCurlAdapterSupportsAndCallsVendorMethods(): void
    {
        $curl = Mockery::mock(Curl::class);
        $curl->shouldReceive('setTimeout')->once()->with(15)->andReturnNull();

        $adapter = new CurlAdapter($curl);

        $this->assertTrue($adapter->supportsMethod('setTimeout'));
        $this->assertFalse($adapter->supportsMethod('missingMethod'));
        $this->assertNull($adapter->callMethod('setTimeout', [15]));
    }

    public function testCurlAdapterSupportsAndCallsNativeFacadeMethods(): void
    {
        $adapter = new CurlAdapter();

        $this->assertTrue($adapter->supportsMethod('setHeaders'));
        $this->assertFalse($adapter->supportsMethod('setTimeout'));
        $this->assertSame($adapter, $adapter->callMethod('setHeaders', [['Accept' => 'application/json']]));
    }
}
