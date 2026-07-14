<?php

namespace Quantum\Tests\Unit\HttpClient\Adapters;

use Quantum\HttpClient\Adapters\MultiCurlAdapter;
use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Curl\MultiCurl;
use Curl\Curl;
use Mockery;

class MultiCurlAdapterTest extends AppTestCase
{
    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testMultiCurlAdapterDelegatesRequestMethods(): void
    {
        $getCurl = Mockery::mock(Curl::class);
        $postCurl = Mockery::mock(Curl::class);

        $multiCurl = Mockery::mock(MultiCurl::class);
        $multiCurl->shouldReceive('setHeader')->once()->with('Accept', 'application/json');
        $multiCurl->shouldReceive('setHeaders')->once()->with(['X-Test' => 'yes']);
        $multiCurl->shouldReceive('setOpt')->once()->with(CURLOPT_TIMEOUT, 10);
        $multiCurl->shouldReceive('setOpts')->once()->with([CURLOPT_CONNECTTIMEOUT => 5]);
        $multiCurl->shouldReceive('addGet')->once()->with('https://example.com', ['a' => 1])->andReturn($getCurl);
        $multiCurl->shouldReceive('addPost')->once()->with('https://example.com', 'payload', true)->andReturn($postCurl);
        $multiCurl->shouldReceive('start')->once()->andReturnNull();

        $adapter = new MultiCurlAdapter($multiCurl);

        $this->assertSame($adapter, $adapter->setHeader('Accept', 'application/json'));
        $this->assertSame($adapter, $adapter->setHeaders(['X-Test' => 'yes']));
        $this->assertSame($adapter, $adapter->setOpt(CURLOPT_TIMEOUT, 10));
        $this->assertSame($adapter, $adapter->setOpts([CURLOPT_CONNECTTIMEOUT => 5]));
        $this->assertInstanceOf(CurlAdapter::class, $adapter->addGet('https://example.com', ['a' => 1]));
        $this->assertInstanceOf(CurlAdapter::class, $adapter->addPost('https://example.com', 'payload', true));
        $this->assertNull($adapter->start());
    }

    public function testMultiCurlAdapterRegistersCallbacks(): void
    {
        $success = fn () => null;
        $error = fn () => null;

        $multiCurl = Mockery::mock(MultiCurl::class);
        $multiCurl->shouldReceive('success')->once()->with($success)->andReturnNull();
        $multiCurl->shouldReceive('error')->once()->with($error)->andReturnNull();

        $adapter = new MultiCurlAdapter($multiCurl);

        $this->assertSame($adapter, $adapter->success($success));
        $this->assertSame($adapter, $adapter->error($error));
    }

    public function testMultiCurlAdapterWrapsCompleteCallbackInstance(): void
    {
        $curl = Mockery::mock(Curl::class);

        $multiCurl = Mockery::mock(MultiCurl::class);
        $multiCurl->shouldReceive('complete')
            ->once()
            ->andReturnUsing(function (callable $callback) use ($curl): void {
                $callback($curl);
            });

        $adapter = new MultiCurlAdapter($multiCurl);
        $wrapped = null;

        $this->assertSame($adapter, $adapter->complete(function (CurlAdapter $instance) use (&$wrapped): void {
            $wrapped = $instance;
        }));

        $this->assertInstanceOf(CurlAdapter::class, $wrapped);
    }

    public function testMultiCurlAdapterSupportsDocumentedMethods(): void
    {
        $multiCurl = Mockery::mock(MultiCurl::class);
        $multiCurl->shouldReceive('addGet')->once()->with('https://example.com', [])->andReturn((object) ['id' => 1]);

        $adapter = new MultiCurlAdapter($multiCurl);

        $this->assertTrue($adapter->supportsMethod('addGet'));
        $this->assertFalse($adapter->supportsMethod('missingMethod'));
        $this->assertEquals((object) ['id' => 1], $adapter->callMethod('addGet', ['https://example.com', []]));
    }
}
