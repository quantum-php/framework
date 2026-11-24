<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang\Traits;

use Quantum\HttpClient\Exceptions\HttpClientException;
use Quantum\Lang\Adapters\GoogleTranslateAdapter;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Lang\Adapters\DeepLAdapter;
use Quantum\Lang\Adapters\FileAdapter;
use Quantum\Di\Exceptions\DiException;
use Quantum\HttpClient\HttpClient;
use Quantum\Config\ConfigSection;
use ReflectionException;
use ErrorException;

trait RemoteAdapterTrait
{
    protected HttpClient $httpClient;

    protected ConfigSection $config;

    /**
     * @param array<int|string, mixed>|string|null $params
     * @throws LangException|ConfigException|DiException|BaseException|ReflectionException
     */
    protected function buildSourceText(string $key, array|string $params = null): string
    {
        if ($this->usesSourceCatalog()) {
            $sourceLocale = $this->getSourceLocale();

            if ($sourceLocale === null || $sourceLocale === '') {
                throw LangException::missingConfig($this->getSourceLocaleConfigPath());
            }

            return (new FileAdapter($sourceLocale))->get($key, $params);
        }

        return $params ? _message($key, $params) : $key;
    }

    protected function shouldBypassProvider(string $key, string $text): bool
    {
        if ($text === '') {
            return true;
        }

        if (!$this->usesSourceCatalog()) {
            return false;
        }

        $sourceLocale = $this->getSourceLocale();

        return $text === $key
            || ($sourceLocale !== null && strcasecmp($sourceLocale, $this->lang) === 0);
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    protected function getCachedTranslation(string $adapter, string $text): ?string
    {
        if (!$this->isCacheEnabled()) {
            return null;
        }

        $cacheItem = cache($this->getCacheAdapter())->get($this->getCacheKey($adapter, $text));

        return is_string($cacheItem) ? $cacheItem : null;
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    protected function setCachedTranslation(string $adapter, string $text, string $translation): void
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        cache($this->getCacheAdapter())->set(
            $this->getCacheKey($adapter, $text),
            $translation,
            $this->getCacheTtl()
        );
    }

    /**
     * @param array<string, mixed>|string|null $data
     * @param array<string, mixed> $headers
     * @param array<int, mixed> $options
     * @param string $method
     * @throws LangException|HttpClientException|ErrorException|BaseException
     */
    protected function sendRequest(string $url, $data = null, array $headers = [], array $options = [], string $method = 'POST'): mixed
    {
        $request = $this->httpClient
            ->createRequest($url)
            ->setMethod($method);

        if ($data !== null) {
            $request->setData($data);
        }

        if ($headers) {
            $request->setHeaders($headers);
        }

        foreach ($options as $option => $value) {
            $request->setOpt($option, $value);
        }

        $request->start();

        $errors = $this->httpClient->getErrors();
        $responseBody = $this->httpClient->getResponseBody();

        if ($errors) {
            throw LangException::providerRequestFailed(
                static::class,
                json_encode($responseBody ?? $errors) ?: null
            );
        }

        return $responseBody;
    }

    private function isCacheEnabled(): bool
    {
        return $this->config->getBool('cache.enabled');
    }

    private function getCacheAdapter(): string
    {
        return (string) ($this->config->get('cache.default') ?? '');
    }

    private function getCacheTtl(): ?int
    {
        if ($this->config->get('cache.ttl') === null) {
            return null;
        }

        return $this->config->getInt('cache.ttl');
    }

    private function getCacheKey(string $adapter, string $text): string
    {
        $prefix = (string) ($this->config->get('cache.prefix') ?? '');
        $sourceLocale = (string) ($this->config->get('source_locale') ?? 'auto');

        return $prefix . sha1($adapter . '|' . $this->lang . '|' . $sourceLocale . '|' . $text);
    }

    private function usesSourceCatalog(): bool
    {
        return $this->config->getBool('use_source_catalog');
    }

    private function getSourceLocale(): ?string
    {
        $sourceLocale = $this->config->get('source_locale');

        if ($sourceLocale === null || $sourceLocale === '') {
            return null;
        }

        return (string) $sourceLocale;
    }

    private function getSourceLocaleConfigPath(): string
    {
        return match (static::class) {
            DeepLAdapter::class => 'lang.deepl.source_locale',
            GoogleTranslateAdapter::class => 'lang.google_translate.source_locale',
            default => 'lang.source_locale',
        };
    }
}
