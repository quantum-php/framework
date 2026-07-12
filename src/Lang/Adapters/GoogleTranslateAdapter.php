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

class GoogleTranslateAdapter implements LangAdapterInterface
{
    use RemoteAdapterTrait;

    public const API_URL = 'https://translation.googleapis.com/language/translate/v2';

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
     * @throws LangException|BaseException|ReflectionException|ErrorException
     */
    public function get(string $key, $params = null): string
    {
        $text = $this->buildSourceText($key, $params);

        if ($this->shouldBypassProvider($key, $text)) {
            return $text;
        }

        $cached = $this->getCachedTranslation('google_translate', $text);

        if ($cached !== null) {
            return $cached;
        }

        $apiKey = (string) ($this->params['api_key'] ?? '');

        if ($apiKey === '') {
            throw LangException::missingConfig('lang.google_translate.api_key');
        }

        $response = $this->sendRequest(
            (string) ($this->params['api_url'] ?? self::API_URL) . '?key=' . urlencode($apiKey),
            $this->buildPayload($text),
            [
                'Content-Type' => 'application/json',
            ],
        );

        $translation = $this->extractTranslation($response);

        $this->setCachedTranslation('google_translate', $text, $translation);

        return $translation;
    }

    /**
     * @throws LangException
     */
    private function buildPayload(string $text): string
    {
        $payload = [
            'q' => $text,
            'target' => $this->lang,
            'format' => 'text',
        ];

        if (!empty($this->params['source_locale'])) {
            $payload['source'] = (string) $this->params['source_locale'];
        }

        $payloadJson = json_encode($payload);

        if ($payloadJson === false) {
            throw LangException::payloadEncodingFailed('Google Translate');
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
            || !isset($response->data)
            || !is_object($response->data)
            || !isset($response->data->translations)
            || !is_array($response->data->translations)
            || !isset($response->data->translations[0]->translatedText)
            || !is_string($response->data->translations[0]->translatedText)
        ) {
            throw LangException::invalidProviderResponse('Google Translate');
        }

        return html_entity_decode($response->data->translations[0]->translatedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
