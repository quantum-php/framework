<?php

namespace Quantum\Tests\Unit\HttpClient\Adapters;

use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\HttpClient\ResponseParser;
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

    public function testCurlAdapterPassesZeroInfoOptionToNativeCurl(): void
    {
        $adapter = new CurlAdapter();

        $this->assertFalse($adapter->getInfo(0));
    }

    public function testCurlAdapterResolvesClientErrorState(): void
    {
        $adapter = new CurlAdapter();

        $this->setPrivateProperty(
            $adapter,
            'responseHeaders',
            (new ResponseParser())->parseHeaders("HTTP/1.1 404 Not Found\r\n\r\n")
        );

        $this->invokePrivateMethod($adapter, 'applyErrorState', [0, '', 404]);

        $this->assertTrue($adapter->isError());
        $this->assertSame(404, $adapter->getErrorCode());
        $this->assertSame('HTTP/1.1 404 Not Found', $adapter->getErrorMessage());
    }

    public function testCurlAdapterResolvesServerErrorState(): void
    {
        $adapter = new CurlAdapter();

        $this->setPrivateProperty(
            $adapter,
            'responseHeaders',
            (new ResponseParser())->parseHeaders("HTTP/1.1 500 Internal Server Error\r\n\r\n")
        );

        $this->invokePrivateMethod($adapter, 'applyErrorState', [0, '', 500]);

        $this->assertTrue($adapter->isError());
        $this->assertSame(500, $adapter->getErrorCode());
        $this->assertSame('HTTP/1.1 500 Internal Server Error', $adapter->getErrorMessage());
    }

    public function testCurlAdapterUsesEmptyMessageWhenErrorResponseHasNoStatusLine(): void
    {
        $adapter = new CurlAdapter();

        $this->invokePrivateMethod($adapter, 'applyErrorState', [0, '', 503]);

        $this->assertTrue($adapter->isError());
        $this->assertSame(503, $adapter->getErrorCode());
        $this->assertSame('', $adapter->getErrorMessage());
    }

    public function testCurlAdapterClearsErrorStateWhenResponseSucceeds(): void
    {
        $adapter = new CurlAdapter();

        $this->invokePrivateMethod($adapter, 'applyErrorState', [0, '', 500]);
        $this->invokePrivateMethod($adapter, 'applyErrorState', [0, '', 200]);

        $this->assertFalse($adapter->isError());
        $this->assertSame(0, $adapter->getErrorCode());
        $this->assertNull($adapter->getErrorMessage());
    }

    public function testCurlAdapterPrefersCurlErrorOverHttpStatus(): void
    {
        $adapter = new CurlAdapter();

        $this->invokePrivateMethod($adapter, 'applyErrorState', [CURLE_COULDNT_RESOLVE_HOST, 'Could not resolve host: example.test', 404]);

        $this->assertTrue($adapter->isError());
        $this->assertSame(CURLE_COULDNT_RESOLVE_HOST, $adapter->getErrorCode());
        $this->assertSame(
            curl_strerror(CURLE_COULDNT_RESOLVE_HOST) . ': Could not resolve host: example.test',
            $adapter->getErrorMessage()
        );
    }

    public function testCurlAdapterUsesCurlErrorTextWhenCurlGivesNoMessage(): void
    {
        $adapter = new CurlAdapter();

        $this->invokePrivateMethod($adapter, 'applyErrorState', [CURLE_COULDNT_CONNECT, '', 0]);

        $this->assertTrue($adapter->isError());
        $this->assertSame(CURLE_COULDNT_CONNECT, $adapter->getErrorCode());
        $this->assertSame(curl_strerror(CURLE_COULDNT_CONNECT), $adapter->getErrorMessage());
    }

    public function testCurlAdapterStoresNativeCurlErrorState(): void
    {
        $adapter = new CurlAdapter();

        $adapter
            ->setUrl($this->fileUrl(PROJECT_ROOT . DS . 'missing-curl-fixture.txt'))
            ->start();

        $this->assertTrue($adapter->isError());
        $this->assertSame(CURLE_FILE_COULDNT_READ_FILE, $adapter->getErrorCode());

        $errorText = (string) curl_strerror(CURLE_FILE_COULDNT_READ_FILE);
        $errorDetail = curl_error($adapter->getHandle());

        $expectedMessage = $errorDetail !== '' ? $errorText . ': ' . $errorDetail : $errorText;

        $this->assertSame($expectedMessage, $adapter->getErrorMessage());
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
