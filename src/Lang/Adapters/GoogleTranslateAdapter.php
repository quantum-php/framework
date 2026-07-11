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

        if ($text === '') {
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
            throw LangException::invalidProviderResponse('Google Translate');
        }

        $response = $this->sendRequest(
            (string) ($this->params['api_url'] ?? self::API_URL) . '?key=' . urlencode($apiKey),
            $payloadJson,
            [
                'Content-Type' => 'application/json',
            ],
            'POST'
        );

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

        $translation = html_entity_decode($response->data->translations[0]->translatedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->setCachedTranslation('google_translate', $text, $translation);

        return $translation;
    }
}
