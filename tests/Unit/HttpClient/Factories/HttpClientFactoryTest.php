<?php

namespace Quantum\Tests\Unit\HttpClient\Factories;

use Quantum\HttpClient\Factories\HttpClientFactory;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\HttpClient\HttpClient;
use Quantum\Di\Di;

class HttpClientFactoryTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->resetHttpClientFactory();
    }

    public function testHttpClientFactoryInstance(): void
    {
        $this->assertInstanceOf(HttpClient::class, HttpClientFactory::get());
    }

    public function testHttpClientFactoryReturnsSameInstance(): void
    {
        $httpClient1 = HttpClientFactory::get();
        $httpClient2 = HttpClientFactory::get();

        $this->assertSame($httpClient1, $httpClient2);
    }

    public function testHttpClientFactoryResolveReturnsSameInstance(): void
    {
        $factory = Di::get(HttpClientFactory::class);

        $httpClient1 = $factory->resolve();
        $httpClient2 = $factory->resolve();

        $this->assertSame($httpClient1, $httpClient2);
    }

    private function resetHttpClientFactory(): void
    {
        if (!Di::isRegistered(HttpClientFactory::class)) {
            Di::register(HttpClientFactory::class);
        }

        $factory = Di::get(HttpClientFactory::class);
        $this->setPrivateProperty($factory, 'instance', null);
    }
}
