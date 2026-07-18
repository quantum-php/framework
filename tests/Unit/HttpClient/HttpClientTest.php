<?php

namespace Quantum\Tests\Unit\HttpClient;

use Quantum\HttpClient\Exceptions\HttpClientException;
use Quantum\HttpClient\Adapters\MultiCurlAdapter;
use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\HttpClient\HttpClient;
use Quantum\Tests\Unit\AppTestCase;
use Curl\CaseInsensitiveArray;
use Curl\MultiCurl;
use Curl\Curl;
use Mockery;

class HttpClientTest extends AppTestCase
{
    private HttpClient $httpClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->httpClient = new HttpClient();
    }

    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testHttpClientGetSetMethod(): void
    {
        $this->assertNull($this->httpClient->getAdapter());

        $this->assertEquals('GET', $this->httpClient->getMethod());

        $this->httpClient->setMethod('POST');

        $this->assertEquals('POST', $this->httpClient->getMethod());

        $this->expectException(HttpClientException::class);

        $this->httpClient->setMethod('NOPE');
    }

    public function testHttpClientGetSetData(): void
    {
        $this->assertNull($this->httpClient->getData());

        $data = ['a' => 1];

        $this->httpClient->setData($data);

        $this->assertSame($data, $this->httpClient->getData());
    }

    public function testHttpClientIsMultiRequest(): void
    {
        $curl = Mockery::mock(Curl::class);

        $multi = Mockery::mock(MultiCurl::class);

        $multi->shouldReceive('complete')->once();

        $this->httpClient->createRequest('https://example.com', $curl);

        $this->assertFalse($this->httpClient->isMultiRequest());

        $this->assertInstanceOf(CurlAdapter::class, $this->httpClient->getAdapter());

        $this->httpClient->createMultiRequest($multi);

        $this->assertTrue($this->httpClient->isMultiRequest());

        $this->assertInstanceOf(MultiCurlAdapter::class, $this->httpClient->getAdapter());
    }

    public function testHttpClientRequestNotCreated(): void
    {
        $this->expectException(HttpClientException::class);

        $this->httpClient->start();
    }

    public function testHttpClientReturnsEmptyResponseAndErrorsBeforeRequestCreated(): void
    {
        $this->assertSame([], $this->httpClient->getResponse());

        $this->assertSame([], $this->httpClient->getErrors());
    }

    public function testHttpClientEnsureSingleRequestThrowsOnMulti(): void
    {
        $multi = Mockery::mock(MultiCurl::class);

        $multi->shouldReceive('complete')->once();

        $this->httpClient->createMultiRequest($multi);

        $this->expectException(HttpClientException::class);

        $this->httpClient->getRequestHeaders();
    }

    public function testHttpClientSingleRequestResponseFlow(): void
    {
        $curl = Mockery::mock(Curl::class);
        $curl->shouldReceive('exec')->once();
        $curl->shouldReceive('isError')->andReturn(false);
        $curl->shouldReceive('getId')->andReturn(0);
        $curl->shouldReceive('getResponseHeaders')
            ->andReturn(new CaseInsensitiveArray(['Content-Type' => 'text/plain']));
        $curl->shouldReceive('getResponseCookies')->andReturn(['a' => 'b']);
        $curl->shouldReceive('getResponse')->andReturn('ok');

        $this->httpClient
            ->createRequest('https://example.com', $curl)
            ->start();

        $this->assertEquals('text/plain', $this->httpClient->getResponseHeaders('content-type'));

        $this->assertEquals('b', $this->httpClient->getResponseCookies('a'));

        $this->assertEquals('ok', $this->httpClient->getResponseBody());
    }

    public function testHttpClientPostRequestWithData(): void
    {
        $curl = Mockery::mock(Curl::class);
        $curl->shouldReceive('exec')->once();
        $curl->shouldReceive('isError')->andReturn(false);
        $curl->shouldReceive('getId')->andReturn(0);
        $curl->shouldReceive('getResponseHeaders')->andReturn(new CaseInsensitiveArray());
        $curl->shouldReceive('getResponseCookies')->andReturn([]);
        $curl->shouldReceive('getResponse')->andReturn((object) ['status' => 'ok']);

        $this->httpClient
            ->createRequest('https://example.com', $curl)
            ->setMethod('POST')
            ->setData(['x' => 1])
            ->start();

        $this->assertEquals('ok', $this->httpClient->getResponseBody()->status);
    }

    public function testHttpClientSingleRequestError(): void
    {
        $curl = Mockery::mock(Curl::class);
        $curl->shouldReceive('exec')->once();
        $curl->shouldReceive('isError')->andReturn(true);
        $curl->shouldReceive('getId')->andReturn(0);
        $curl->shouldReceive('getErrorCode')->andReturn(6);
        $curl->shouldReceive('getErrorMessage')->andReturn('DNS error');
        $curl->shouldReceive('getResponseHeaders')->andReturn(new CaseInsensitiveArray());
        $curl->shouldReceive('getResponseCookies')->andReturn([]);
        $curl->shouldReceive('getResponse')->andReturn(null);

        $this->httpClient
            ->createRequest('https://bad.local', $curl)
            ->start();

        $errors = $this->httpClient->getErrors();

        $this->assertEquals(6, $errors['code']);

        $this->assertEquals('DNS error', $errors['message']);
    }

    public function testHttpClientMultiRequestResponseStructure(): void
    {
        $multi = Mockery::mock(MultiCurl::class);
        $multi->shouldReceive('complete')
            ->once()
            ->andReturnUsing(function ($callback): void {
                $curl = Mockery::mock(Curl::class);
                $curl->shouldReceive('isError')->andReturn(false);
                $curl->shouldReceive('getId')->andReturn(0);
                $curl->shouldReceive('getResponseHeaders')->andReturn(new CaseInsensitiveArray());
                $curl->shouldReceive('getResponseCookies')->andReturn([]);
                $curl->shouldReceive('getResponse')->andReturn('ok');

                $callback($curl);
            });

        $this->httpClient->createMultiRequest($multi);

        $response = $this->httpClient->getResponse();

        $this->assertArrayHasKey(0, $response);

        $this->assertArrayHasKey('headers', $response[0]);

        $this->assertArrayHasKey('cookies', $response[0]);

        $this->assertArrayHasKey('body', $response[0]);
    }

    public function testHttpClientMultiRequestAggregatesErrors(): void
    {
        $multi = Mockery::mock(MultiCurl::class);
        $multi->shouldReceive('complete')
            ->once()
            ->andReturnUsing(function ($callback): void {
                foreach ([0, 1] as $id) {
                    $curl = Mockery::mock(Curl::class);
                    $curl->shouldReceive('isError')->andReturn(true);
                    $curl->shouldReceive('getId')->andReturn($id);
                    $curl->shouldReceive('getErrorCode')->andReturn(6);
                    $curl->shouldReceive('getErrorMessage')->andReturn('DNS error');
                    $curl->shouldReceive('getResponseHeaders')->andReturn(new CaseInsensitiveArray());
                    $curl->shouldReceive('getResponseCookies')->andReturn([]);
                    $curl->shouldReceive('getResponse')->andReturn(null);

                    $callback($curl);
                }
            });

        $this->httpClient->createMultiRequest($multi);

        $errors = $this->httpClient->getErrors();

        $this->assertCount(2, $errors);

        $this->assertEquals(6, $errors[0]['code']);

        $this->assertEquals(6, $errors[1]['code']);
    }

    public function testHttpClientCreateAsyncMultiRequestRegistersCallbacks(): void
    {
        $curl = Mockery::mock(Curl::class);
        $successWrapped = null;
        $errorWrapped = null;
        $success = function (CurlAdapter $instance) use (&$successWrapped): void {
            $successWrapped = $instance;
        };
        $error = function (CurlAdapter $instance) use (&$errorWrapped): void {
            $errorWrapped = $instance;
        };

        $multi = Mockery::mock(MultiCurl::class);
        $multi->shouldReceive('success')
            ->once()
            ->andReturnUsing(function (callable $callback) use ($curl): void {
                $callback($curl);
            });
        $multi->shouldReceive('error')
            ->once()
            ->andReturnUsing(function (callable $callback) use ($curl): void {
                $callback($curl);
            });

        $this->httpClient->createAsyncMultiRequest($success, $error, $multi);

        $this->assertTrue($this->httpClient->isMultiRequest());

        $this->assertInstanceOf(MultiCurlAdapter::class, $this->httpClient->getAdapter());

        $this->assertInstanceOf(CurlAdapter::class, $successWrapped);

        $this->assertInstanceOf(CurlAdapter::class, $errorWrapped);
    }

    public function testHttpClientInfoAndUrl(): void
    {
        $curl = Mockery::mock(Curl::class);
        $curl->shouldReceive('exec')->once();
        $curl->shouldReceive('isError')->andReturn(false);
        $curl->shouldReceive('getId')->andReturn(0);
        $curl->shouldReceive('getResponseHeaders')->andReturn(new CaseInsensitiveArray());
        $curl->shouldReceive('getResponseCookies')->andReturn([]);
        $curl->shouldReceive('getResponse')->andReturn('');
        $curl->shouldReceive('getInfo')->andReturnUsing(
            fn ($opt = null) => $opt === CURLINFO_HTTP_CODE ? 200 : ['http_code' => 200]
        );

        $this->httpClient
            ->createRequest('https://example.com', $curl)
            ->start();

        $this->assertEquals(200, $this->httpClient->info(CURLINFO_HTTP_CODE));

        $this->assertEquals('https://example.com', $this->httpClient->url());
    }

    public function testHttpClientPassesZeroInfoOption(): void
    {
        $curl = Mockery::mock(Curl::class);
        $curl->shouldReceive('getInfo')->with(0)->once()->andReturn('zero');

        $this->httpClient->createRequest('https://example.com', $curl);

        $this->assertSame('zero', $this->httpClient->info(0));
    }
}
