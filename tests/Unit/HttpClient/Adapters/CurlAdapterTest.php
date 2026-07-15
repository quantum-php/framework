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
        $curl = Mockery::mock(Curl::class);
        $curl->shouldReceive('setUrl')->once()->with('https://example.com');
        $curl->shouldReceive('setHeader')->once()->with('Accept', 'application/json');
        $curl->shouldReceive('setHeaders')->once()->with(['X-Test' => 'yes']);
        $curl->shouldReceive('setOpt')->once()->with(CURLOPT_TIMEOUT, 10);
        $curl->shouldReceive('setOpts')->once()->with([CURLOPT_CONNECTTIMEOUT => 5]);
        $curl->shouldReceive('buildPostData')->once()->with(['a' => 1])->andReturn('a=1');
        $curl->shouldReceive('exec')->once()->andReturn('ok');

        $adapter = new CurlAdapter($curl);

        $this->assertSame($adapter, $adapter->setUrl('https://example.com'));
        $this->assertSame($adapter, $adapter->setHeader('Accept', 'application/json'));
        $this->assertSame($adapter, $adapter->setHeaders(['X-Test' => 'yes']));
        $this->assertSame($adapter, $adapter->setOpt(CURLOPT_TIMEOUT, 10));
        $this->assertSame($adapter, $adapter->setOpts([CURLOPT_CONNECTTIMEOUT => 5]));
        $this->assertSame('a=1', $adapter->buildPostData(['a' => 1]));
        $this->assertSame('ok', $adapter->start());
    }

    public function testCurlAdapterDelegatesResponseMethods(): void
    {
        $headers = new CaseInsensitiveArray(['Content-Type' => 'application/json']);
        $response = (object) ['ok' => true];

        $curl = Mockery::mock(Curl::class);
        $curl->shouldReceive('getId')->once()->andReturn(7);
        $curl->shouldReceive('isError')->once()->andReturn(false);
        $curl->shouldReceive('getErrorCode')->once()->andReturn(0);
        $curl->shouldReceive('getErrorMessage')->once()->andReturn(null);
        $curl->shouldReceive('getResponseHeaders')->once()->andReturn($headers);
        $curl->shouldReceive('getResponseCookies')->once()->andReturn(['sid' => 'abc']);
        $curl->shouldReceive('getResponse')->once()->andReturn($response);
        $curl->shouldReceive('getInfo')->with(CURLINFO_HTTP_CODE)->once()->andReturn(200);
        $curl->shouldReceive('getUrl')->once()->andReturn('https://example.com');

        $adapter = new CurlAdapter($curl);

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
}
