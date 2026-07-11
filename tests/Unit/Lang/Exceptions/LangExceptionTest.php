<?php

namespace Quantum\Tests\Unit\Lang\Exceptions;

use Quantum\Lang\Exceptions\LangException;
use Quantum\Tests\Unit\AppTestCase;

class LangExceptionTest extends AppTestCase
{
    public function testTranslationsNotFound(): void
    {
        $exception = LangException::translationsNotFound();

        $this->assertInstanceOf(LangException::class, $exception);
        $this->assertSame('Translation files not found.', $exception->getMessage());
        $this->assertSame(E_WARNING, $exception->getCode());
    }

    public function testMisconfiguredDefaultConfig(): void
    {
        $exception = LangException::misconfiguredDefaultConfig();

        $this->assertInstanceOf(LangException::class, $exception);
        $this->assertSame('Misconfigured lang default config.', $exception->getMessage());
        $this->assertSame(E_WARNING, $exception->getCode());
    }

    public function testMisconfiguredDefaultAdapterConfig(): void
    {
        $exception = LangException::misconfiguredDefaultAdapterConfig();

        $this->assertInstanceOf(LangException::class, $exception);
        $this->assertSame('Misconfigured lang default adapter config.', $exception->getMessage());
        $this->assertSame(E_WARNING, $exception->getCode());
    }

    public function testInvalidProviderResponse(): void
    {
        $exception = LangException::invalidProviderResponse('DeepL');

        $this->assertInstanceOf(LangException::class, $exception);
        $this->assertSame('The provider `DeepL` returned an invalid translation response.', $exception->getMessage());
        $this->assertSame(E_WARNING, $exception->getCode());
    }

    public function testProviderRequestFailedWithoutDetails(): void
    {
        $exception = LangException::providerRequestFailed('Google Translate');

        $this->assertInstanceOf(LangException::class, $exception);
        $this->assertSame('The translation request to `Google Translate` failed.', $exception->getMessage());
        $this->assertSame(E_WARNING, $exception->getCode());
    }

    public function testProviderRequestFailedWithDetails(): void
    {
        $exception = LangException::providerRequestFailed('Google Translate', 'timeout');

        $this->assertInstanceOf(LangException::class, $exception);
        $this->assertSame('The translation request to `Google Translate` failed: timeout.', $exception->getMessage());
        $this->assertSame(E_WARNING, $exception->getCode());
    }
}
