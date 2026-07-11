<?php

namespace Quantum\Tests\Unit\Lang\Adapters;

use Quantum\Tests\Unit\Storage\HttpClientTestCase;
use Quantum\Lang\Exceptions\LangException;
use Quantum\Lang\Adapters\DeepLAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\HttpClient\HttpClient;
use Mockery;

class DeepLAdapterTest extends AppTestCase
{
    use HttpClientTestCase;

    private string $cachePrefix;

    public function setUp(): void
    {
        parent::setUp();

        $this->cachePrefix = 'lang_deepl_test_' . uniqid('', true) . ':';
    }

    public function tearDown(): void
    {
        cache('file')->getAdapter()->clear();
        Mockery::close();

        parent::tearDown();
    }

    public function testDeepLAdapterReturnsProviderTranslationAndCachesIt(): void
    {
        $this->currentResponse = (object) [
            'translations' => [
                (object) ['text' => 'Hola'],
            ],
        ];

        $adapter = new DeepLAdapter('es', $this->getParams(), $this->mockHttpClient());

        $this->assertEquals('Hola', $adapter->get('Hello'));
    }

    public function testDeepLAdapterReturnsCachedTranslationWithoutProviderCall(): void
    {
        $this->currentResponse = (object) [
            'translations' => [
                (object) ['text' => 'Hola'],
            ],
        ];

        $firstAdapter = new DeepLAdapter('es', $this->getParams(), $this->mockHttpClient());

        $this->assertEquals('Hola', $firstAdapter->get('Hello'));

        $httpClientMock = Mockery::mock(HttpClient::class);
        $httpClientMock->shouldNotReceive('createRequest');

        $secondAdapter = new DeepLAdapter('es', $this->getParams(), $httpClientMock);

        $this->assertEquals('Hola', $secondAdapter->get('Hello'));
    }

    public function testDeepLAdapterThrowsIfAuthKeyIsMissing(): void
    {
        $adapter = new DeepLAdapter('es', $this->getParams(['auth_key' => '']), $this->mockHttpClient());

        $this->expectException(LangException::class);
        $this->expectExceptionMessage('Could not load config `lang.deepl.auth_key` properly.');

        $adapter->get('Hello');
    }

    public function testDeepLAdapterThrowsIfProviderResponseIsInvalid(): void
    {
        $this->currentResponse = (object) [];

        $adapter = new DeepLAdapter('es', $this->getParams(), $this->mockHttpClient());

        $this->expectException(LangException::class);
        $this->expectExceptionMessage('The provider `DeepL` returned an invalid translation response.');

        $adapter->get('Hello');
    }

    public function testDeepLAdapterReturnsEmptyStringWithoutProviderCall(): void
    {
        $httpClientMock = Mockery::mock(HttpClient::class);
        $httpClientMock->shouldNotReceive('createRequest');

        $adapter = new DeepLAdapter('es', $this->getParams(), $httpClientMock);

        $this->assertSame('', $adapter->get(''));
    }

    public function testDeepLAdapterThrowsIfProviderRequestFails(): void
    {
        $this->currentErrors = ['timeout'];

        $adapter = new DeepLAdapter('es', $this->getParams(), $this->mockHttpClient());

        $this->expectException(LangException::class);
        $this->expectExceptionMessage('The translation request to `Quantum\\Lang\\Adapters\\DeepLAdapter` failed');

        $adapter->get('Hello');
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function getParams(array $overrides = []): array
    {
        return array_replace_recursive([
            'auth_key' => 'test-auth-key',
            'source_locale' => 'en',
            'cache' => [
                'enabled' => true,
                'default' => 'file',
                'ttl' => 3600,
                'prefix' => $this->cachePrefix,
            ],
        ], $overrides);
    }
}
