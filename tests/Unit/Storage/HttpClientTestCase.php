<?php

namespace Quantum\Tests\Unit\Storage;

use Quantum\HttpClient\HttpClient;
use Mockery;

trait HttpClientTestCase
{
    protected $url;

    protected $response;

    protected $currentResponse;

    protected $currentErrors;

    protected $data;

    protected $options;

    protected function mockHttpClient()
    {
        $httpClientMock = Mockery::mock(HttpClient::class);

        $httpClientMock->shouldReceive('createRequest')->andReturnUsing(function ($url) use ($httpClientMock) {
            $this->url = $url;
            return $httpClientMock;
        });

        $httpClientMock->shouldReceive('setMethod')->andReturnSelf();

        $httpClientMock->shouldReceive('setHeaders')->andReturnSelf();

        $httpClientMock->shouldReceive('setOpt')->andReturnUsing(function ($option, $value) use ($httpClientMock) {
            $this->options[$this->url][$option] = $value;
            return $httpClientMock;
        });

        $httpClientMock->shouldReceive('getRequestHeaders')->andReturn([]);

        $httpClientMock->shouldReceive('getData')->andReturnUsing(fn () => $this->data[$this->url] ?? []);

        $httpClientMock->shouldReceive('setData')->andReturnUsing(function ($data) use ($httpClientMock) {
            $this->data[$this->url] = $data;
            return $httpClientMock;
        });

        $httpClientMock->shouldReceive('start')->andReturnUsing(function () use ($httpClientMock) {
            $this->response[$this->url]['body'] = $this->currentResponse;
            $this->response[$this->url]['errors'] = $this->currentErrors;
            $this->currentResponse = [];
            $this->currentErrors = [];
            return $httpClientMock;
        });

        $httpClientMock->shouldReceive('getErrors')->andReturnUsing(fn (): array => (array) $this->response[$this->url]['errors']);

        $httpClientMock->shouldReceive('getResponseBody')->andReturnUsing(fn () => $this->response[$this->url]['body']);

        $httpClientMock->shouldReceive('url')->andReturnUsing(fn () => $this->url);

        return $httpClientMock;
    }
}
