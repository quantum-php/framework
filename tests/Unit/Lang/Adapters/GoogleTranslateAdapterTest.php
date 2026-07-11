<?php

namespace Quantum\Tests\Unit\Lang\Adapters;

use Mockery;
use Quantum\HttpClient\HttpClient;
use Quantum\Lang\Adapters\GoogleTranslateAdapter;
use Quantum\Lang\Exceptions\LangException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Tests\Unit\Storage\HttpClientTestCase;

class GoogleTranslateAdapterTest extends AppTestCase
{
    use HttpClientTestCase;

    private string $cachePrefix;

    public function setUp(): void
    {
        parent::setUp();

        $this->cachePrefix = 'lang_google_translate_test_' . uniqid('', true) . ':';
    }

    public function tearDown(): void
    {
        cache('file')->getAdapter()->clear();
        Mockery::close();

        parent::tearDown();
    }

    public function testGoogleTranslateAdapterReturnsProviderTranslationAndCachesIt(): void
    {
        $this->currentResponse = (object) [
            'data' => (object) [
                'translations' => [
                    (object) ['translatedText' => 'Hola'],
                ],
            ],
        ];

        $adapter = new GoogleTranslateAdapter('es', $this->getParams(), $this->mockHttpClient());

        $this->assertEquals('Hola', $adapter->get('Hello'));
        $this->assertStringContainsString('?key=test-api-key', $this->url);
        $this->assertStringNotContainsString('q=', $this->url);
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'q' => 'Hello',
                'target' => 'es',
                'format' => 'text',
                'source' => 'en',
            ]),
            (string) $this->data[$this->url]
        );
    }

    public function testGoogleTranslateAdapterReturnsCachedTranslationWithoutProviderCall(): void
    {
        $this->currentResponse = (object) [
            'data' => (object) [
                'translations' => [
                    (object) ['translatedText' => 'Hola'],
                ],
            ],
        ];

        $firstAdapter = new GoogleTranslateAdapter('es', $this->getParams(), $this->mockHttpClient());

        $this->assertEquals('Hola', $firstAdapter->get('Hello'));

        $httpClientMock = Mockery::mock(HttpClient::class);
        $httpClientMock->shouldNotReceive('createRequest');

        $secondAdapter = new GoogleTranslateAdapter('es', $this->getParams(), $httpClientMock);

        $this->assertEquals('Hola', $secondAdapter->get('Hello'));
    }

    public function testGoogleTranslateAdapterThrowsIfApiKeyIsMissing(): void
    {
        $adapter = new GoogleTranslateAdapter('es', $this->getParams(['api_key' => '']), $this->mockHttpClient());

        $this->expectException(LangException::class);
        $this->expectExceptionMessage('Could not load config `lang.google_translate.api_key` properly.');

        $adapter->get('Hello');
    }

    public function testGoogleTranslateAdapterThrowsIfProviderResponseIsInvalid(): void
    {
        $this->currentResponse = (object) [];

        $adapter = new GoogleTranslateAdapter('es', $this->getParams(), $this->mockHttpClient());

        $this->expectException(LangException::class);
        $this->expectExceptionMessage('The provider `Google Translate` returned an invalid translation response.');

        $adapter->get('Hello');
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function getParams(array $overrides = []): array
    {
        return array_replace_recursive([
            'api_key' => 'test-api-key',
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
