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
        $this->assertSame(DeepLAdapter::TIMEOUT, $this->options[$this->url][CURLOPT_TIMEOUT]);
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

    public function testDeepLAdapterThrowsIfPayloadCannotBeEncoded(): void
    {
        $adapter = new DeepLAdapter('es', $this->getParams(), $this->mockHttpClient());

        $this->expectException(LangException::class);
        $this->expectExceptionMessage('The translation payload could not be encoded for `DeepL`.');

        $adapter->get("\xB1");
    }

    public function testDeepLAdapterReturnsEmptyStringWithoutProviderCall(): void
    {
        $httpClientMock = Mockery::mock(HttpClient::class);
        $httpClientMock->shouldNotReceive('createRequest');

        $adapter = new DeepLAdapter('es', $this->getParams(), $httpClientMock);

        $this->assertSame('', $adapter->get(''));
    }

    public function testDeepLAdapterTranslatesResolvedSourceCatalogText(): void
    {
        $this->currentResponse = (object) [
            'translations' => [
                (object) ['text' => 'Hola'],
            ],
        ];

        $adapter = new DeepLAdapter('es', $this->getParams([
            'use_source_catalog' => true,
            'source_locale' => 'en',
        ]), $this->mockHttpClient());

        $this->assertSame('Hola', $adapter->get('custom.test'));
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'text' => ['Testing'],
                'target_lang' => 'ES',
                'source_lang' => 'EN',
            ]),
            (string) $this->data[$this->url]
        );
    }

    public function testDeepLAdapterReturnsResolvedSourceTextWithoutProviderCallWhenTargetMatchesSourceLocale(): void
    {
        $httpClientMock = Mockery::mock(HttpClient::class);
        $httpClientMock->shouldNotReceive('createRequest');

        $adapter = new DeepLAdapter('en', $this->getParams([
            'use_source_catalog' => true,
            'source_locale' => 'en',
        ]), $httpClientMock);

        $this->assertSame('Testing', $adapter->get('custom.test'));
    }

    public function testDeepLAdapterReturnsKeyWithoutProviderCallWhenSourceCatalogMisses(): void
    {
        $httpClientMock = Mockery::mock(HttpClient::class);
        $httpClientMock->shouldNotReceive('createRequest');

        $adapter = new DeepLAdapter('es', $this->getParams([
            'use_source_catalog' => true,
            'source_locale' => 'en',
        ]), $httpClientMock);

        $this->assertSame('custom.missing', $adapter->get('custom.missing'));
    }

    public function testDeepLAdapterThrowsIfSourceCatalogLocaleIsMissing(): void
    {
        $adapter = new DeepLAdapter('es', $this->getParams([
            'use_source_catalog' => true,
            'source_locale' => null,
        ]), $this->mockHttpClient());

        $this->expectException(LangException::class);
        $this->expectExceptionMessage('Could not load config `lang.deepl.source_locale` properly.');

        $adapter->get('custom.test');
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
