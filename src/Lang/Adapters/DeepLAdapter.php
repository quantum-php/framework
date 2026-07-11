<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang\Adapters;

use Quantum\Lang\Contracts\LangAdapterInterface;
use Quantum\Lang\Traits\RemoteAdapterTrait;
use Quantum\Lang\Exceptions\LangException;
use Quantum\App\Exceptions\BaseException;
use Quantum\HttpClient\HttpClient;
use ReflectionException;
use ErrorException;

class DeepLAdapter implements LangAdapterInterface
{
    use RemoteAdapterTrait;

    public const API_URL = 'https://api.deepl.com/v2/translate';

    public const TIMEOUT = 30;

    protected string $lang;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(string $lang, array $params, ?HttpClient $httpClient = null)
    {
        $this->lang = $lang;
        $this->params = $params;
        $this->httpClient = $httpClient ?? new HttpClient();
    }

    public function setLang(string $lang): LangAdapterInterface
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @param array<int|string, mixed>|string|null $params
     * @throws BaseException|ReflectionException|ErrorException
     */
    public function get(string $key, $params = null): string
    {
        $text = $this->buildSourceText($key, $params);

        if ($text === '') {
            return $text;
        }

        $cached = $this->getCachedTranslation('deepl', $text);

        if ($cached !== null) {
            return $cached;
        }

        $authKey = (string) ($this->params['auth_key'] ?? '');

        if ($authKey === '') {
            throw LangException::missingConfig('lang.deepl.auth_key');
        }

        $response = $this->sendRequest(
            (string) ($this->params['api_url'] ?? self::API_URL),
            $this->buildPayload($text),
            [
                'Authorization' => 'DeepL-Auth-Key ' . $authKey,
                'Content-Type' => 'application/json',
            ],
            [
                CURLOPT_TIMEOUT => self::TIMEOUT,
            ]
        );

        $translation = $this->extractTranslation($response);

        $this->setCachedTranslation('deepl', $text, $translation);

        return $translation;
    }

    /**
     * @throws LangException
     */
    private function buildPayload(string $text): string
    {
        $payload = [
            'text' => [$text],
            'target_lang' => strtoupper($this->lang),
        ];

        if (!empty($this->params['source_locale'])) {
            $payload['source_lang'] = strtoupper((string) $this->params['source_locale']);
        }

        $payloadJson = json_encode($payload);

        if ($payloadJson === false) {
            throw LangException::invalidProviderResponse('DeepL');
        }

        return $payloadJson;
    }

    /**
     * @param mixed $response
     * @throws LangException
     */
    private function extractTranslation($response): string
    {
        if (
            !is_object($response)
            || !isset($response->translations)
            || !is_array($response->translations)
            || !isset($response->translations[0]->text)
            || !is_string($response->translations[0]->text)
        ) {
            throw LangException::invalidProviderResponse('DeepL');
        }

        return $response->translations[0]->text;
    }
}
