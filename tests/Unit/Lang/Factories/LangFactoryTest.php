<?php

namespace Quantum\Tests\Unit\Lang\Factories;

use Quantum\Lang\Adapters\GoogleTranslateAdapter;
use Quantum\Lang\Exceptions\LangException;
use Quantum\Lang\Factories\LangFactory;
use Quantum\Lang\Adapters\DeepLAdapter;
use Quantum\Lang\Adapters\FileAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Lang\Lang;
use Quantum\Di\Di;

class LangFactoryTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->resetLangFactory();
    }

    public function testLangFactoryGetLangInstance(): void
    {
        $lang = LangFactory::get();

        $this->assertInstanceOf(Lang::class, $lang);

        $this->assertEquals('en', $lang->getLang());
    }

    public function testLangFactoryGetReturnsSameInstance(): void
    {
        $first = LangFactory::get();

        $second = LangFactory::get();

        $this->assertSame($first, $second);
    }

    public function testLangFactoryResolvesFileAdapter(): void
    {
        $lang = LangFactory::get();

        $this->assertInstanceOf(FileAdapter::class, $lang->getAdapter());
    }

    public function testLangFactoryResolvesDeepLAdapter(): void
    {
        config()->set('lang', [
            'default' => 'deepl',
            'default_locale' => 'en',
            'file' => [],
            'deepl' => [
                'auth_key' => 'test-auth-key',
            ],
            'google_translate' => [
                'api_key' => 'test-api-key',
            ],
            'supported' => ['en', 'es'],
            'url_segment' => 1,
        ]);

        $this->testRequest('http://127.0.0.1/api/rest');

        $lang = LangFactory::get();

        $this->assertInstanceOf(DeepLAdapter::class, $lang->getAdapter());
    }

    public function testLangFactoryResolvesGoogleTranslateAdapter(): void
    {
        config()->set('lang', [
            'default' => 'google_translate',
            'default_locale' => 'en',
            'file' => [],
            'deepl' => [
                'auth_key' => 'test-auth-key',
            ],
            'google_translate' => [
                'api_key' => 'test-api-key',
            ],
            'supported' => ['en', 'es'],
            'url_segment' => 1,
        ]);

        $this->testRequest('http://127.0.0.1/api/rest');

        $lang = LangFactory::get();

        $this->assertInstanceOf(GoogleTranslateAdapter::class, $lang->getAdapter());
    }

    public function testLangFactoryDetectedFromRouteParameter(): void
    {
        $this->testRequest('http://127.0.0.1/es/api/rest');

        $lang = LangFactory::get();

        $this->assertEquals('es', $lang->getLang());
    }

    public function testLangFactoryDetectedFromQueryParameter(): void
    {
        $this->testRequest('http://127.0.0.1/api/rest?lang=es');

        $lang = LangFactory::get();

        $this->assertEquals('es', $lang->getLang());
    }

    public function testLangFactoryDetectedFromAcceptedLangParameter(): void
    {
        $this->testRequest('http://127.0.0.1/api/rest', 'GET', [], ['Accept-Language' => 'es, en;q=0.8, fr;q=0.6']);

        $lang = LangFactory::get();

        $this->assertEquals('es', $lang->getLang());
    }

    public function testLangFactoryFallsBackToDefaultIfNoLangDetected(): void
    {
        $this->testRequest('http://127.0.0.1/api/rest');

        $lang = LangFactory::get();

        $this->assertEquals('en', $lang->getLang());
    }

    public function testLangFactoryFallsBackToDefaultIfProvidedLangIsNotSupported(): void
    {
        config()->set('lang', [
            'default' => 'file',
            'default_locale' => 'en',
            'file' => [],
            'supported' => ['en', 'es'],
            'url_segment' => 1,
        ]);

        $this->testRequest('http://127.0.0.1/fr/api/rest');

        $lang = LangFactory::get();

        $this->assertEquals('en', $lang->getLang());

        $this->resetLangFactory();

        $this->testRequest('http://127.0.0.1/api/rest?lang=fr');

        $lang = LangFactory::get();

        $this->assertEquals('en', $lang->getLang());

        $this->resetLangFactory();

        $this->testRequest('http://127.0.0.1/api/rest', 'GET', [], ['Accept-Language' => 'fr, en;q=0.8, fr;q=0.6']);

        $lang = LangFactory::get();

        $this->assertEquals('en', $lang->getLang());
    }

    public function testLangFactoryThrowsErrorIfNoDefaultLangFound(): void
    {
        config()->set('lang', [
            'default' => 'file',
            'default_locale' => null,
            'file' => [],
            'supported' => ['en', 'es'],
            'url_segment' => 1,
        ]);

        $this->testRequest('http://127.0.0.1/fr/api/rest');

        $this->expectException(LangException::class);

        $this->expectExceptionMessage('Misconfigured lang default config');

        LangFactory::get();
    }

    public function testLangFactoryThrowsErrorIfAdapterIsNotSupported(): void
    {
        config()->set('lang', [
            'default' => 'unknown',
            'default_locale' => 'en',
            'file' => [],
            'supported' => ['en', 'es'],
            'url_segment' => 1,
        ]);

        $this->expectException(LangException::class);
        $this->expectExceptionMessage('The adapter `unknown` is not supported.');

        LangFactory::get();
    }

    private function resetLangFactory(): void
    {
        if (!Di::isRegistered(LangFactory::class)) {
            Di::register(LangFactory::class);
        }

        $factory = Di::get(LangFactory::class);
        $this->setPrivateProperty($factory, 'instances', []);
    }
}
