<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient;

use Quantum\HttpClient\Exceptions\HttpClientException;
use Quantum\App\Exceptions\BaseException;
use Curl\CaseInsensitiveArray;
use Curl\MultiCurl;
use ErrorException;
use Curl\Curl;

/**
 * HttpClient Class
 * @package Quantum\HttpClient
 * @uses php-curl-class/php-curl-class
 * @method object addGet(string $url, array<string, mixed> $data = [])
 * @method object addPost(string $url, string $data = '', bool $follow_303_with_post = false)
 * @method setHeader($key, $value)
 * @method setHeaders($headers)
 * @method setOpt($option, $value)
 */
class HttpClient
{
    /**
     * Available methods
     */
    public const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Response headers section
     */
    public const RESPONSE_HEADERS = 'headers';

    /**
     * Response cookies section
     */
    public const RESPONSE_COOKIES = 'cookies';

    /**
     * Response body section
     */
    public const RESPONSE_BODY = 'body';

    /**
     * @var MultiCurl|Curl
     */
    private Curl|MultiCurl|null $client = null;

    private string $method = 'GET';

    /**
     * @var mixed|null
     */
    private $data;

    /**
     * Request headers section
     * @var array<string, mixed>
     */
    private array $requestHeaders = [];

    /**
     * @var array<string, mixed>
     */
    private array $response = [];

    /**
     * @var array<string, mixed>
     */
    private array $errors = [];

    /**
     * Creates request
     */
    public function createRequest(string $url, ?Curl $client = null): HttpClient
    {
        $this->client = $client ?: new Curl();
        $this->client->setUrl($url);
        return $this;
    }

    /**
     * Creates multi request
     */
    public function createMultiRequest(?MultiCurl $client = null): HttpClient
    {
        $this->client = $client ?: new MultiCurl();

        $this->client->complete(function (Curl $instance): void {
            $this->handleResponse($instance);
        });

        return $this;
    }

    /**
     * Creates async multi request
     */
    public function createAsyncMultiRequest(callable $success, callable $error, ?MultiCurl $client = null): HttpClient
    {
        $this->client = $client ?: new MultiCurl();

        $this->client->success($success);
        $this->client->error($error);

        return $this;
    }

    /**
     * Sets http method
     * @throws BaseException
     */
    public function setMethod(string $method): HttpClient
    {
        if (!in_array($method, self::METHODS)) {
            throw HttpClientException::requestMethodNotAvailable($method);
        }

        $this->method = $method;
        return $this;
    }

    /**
     * Gets the current http method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Sets data
     * @param mixed $data
     */
    public function setData($data): HttpClient
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Gets the data
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Checks if the request is multi cURL
     * @phpstan-assert-if-true MultiCurl $this->client
     * @phpstan-assert-if-false Curl $this->client
     */
    public function isMultiRequest(): bool
    {
        return $this->client instanceof MultiCurl;
    }

    /**
     * Starts the request
     * @throws ErrorException
     * @throws HttpClientException|BaseException
     */
    public function start(): HttpClient
    {
        if (!$this->client) {
            throw HttpClientException::requestNotCreated();
        }

        if ($this->isMultiRequest()) {
            $this->client->start();
        } else {
            $this->startSingleRequest();
        }

        return $this;
    }

    /**
     * Gets single or all request headers
     * @return mixed|null
     * @throws BaseException
     */
    public function getRequestHeaders(?string $header = null)
    {
        $this->ensureSingleRequest();

        if ($header !== null) {
            return $this->requestHeaders[$header] ?? null;
        }

        return $this->requestHeaders;
    }

    /**
     * Gets the response headers
     * @return mixed|null
     * @throws BaseException
     */
    public function getResponseHeaders(?string $header = null)
    {
        $this->ensureSingleRequest();

        $responseHeaders = $this->getResponse()[self::RESPONSE_HEADERS];

        if ($header) {
            return $responseHeaders[$header] ?? null;
        }

        return $responseHeaders;
    }

    /**
     * Gets the response cookies
     * @return mixed|null
     * @throws BaseException
     */
    public function getResponseCookies(?string $cookie = null)
    {
        $this->ensureSingleRequest();

        $responseCookies = $this->getResponse()[self::RESPONSE_COOKIES];

        if ($cookie) {
            return $responseCookies[$cookie] ?? null;
        }

        return $responseCookies;
    }

    /**
     * Gets the response body
     * @return mixed|null
     * @throws BaseException
     */
    public function getResponseBody()
    {
        $this->ensureSingleRequest();

        return $this->response[$this->client->getId()][self::RESPONSE_BODY] ?? null;
    }

    /**
     * Gets the entire response
     * @return array<string, mixed>
     */
    public function getResponse(): array
    {
        return $this->isMultiRequest() ? $this->response : ($this->response[$this->client->getId()] ?? []);
    }

    /**
     * Returns the errors
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->isMultiRequest() ? $this->errors : ($this->errors[$this->client->getId()] ?? []);
    }

    /**
     * Gets the curl info
     * @return mixed
     * @throws BaseException
     */
    public function info(?int $option = null)
    {
        $this->ensureSingleRequest();

        return $option ? $this->client->getInfo($option) : $this->client->getInfo();
    }

    /**
     * Gets the current url being executed
     * @throws BaseException
     */
    public function url(): ?string
    {
        $this->ensureSingleRequest();

        return $this->client->getUrl();
    }

    /**
     * @param array<mixed> $arguments
     * @throws BaseException
     * @throws HttpClientException
     */
    public function __call(string $method, array $arguments): HttpClient
    {
        if (is_null($this->client)) {
            throw HttpClientException::requestNotCreated();
        }

        if (!method_exists($this->client, $method)) {
            throw HttpClientException::methodNotSupported($method, $this->client::class);
        }

        $this->interceptCall($method, $arguments);

        $this->client->$method(...$arguments);

        return $this;
    }

    /**
     * @throws ErrorException|BaseException
     */
    private function startSingleRequest(): void
    {
        $this->ensureSingleRequest();

        $this->client->setOpt(CURLOPT_CUSTOMREQUEST, $this->method);

        if ($this->data) {
            $this->client->setOpt(CURLOPT_POSTFIELDS, $this->client->buildPostData($this->data));
        }

        $this->client->exec();
        $this->handleResponse($this->client);
    }

    /**
     * Handles the response
     */
    private function handleResponse(Curl $instance): void
    {
        if ($instance->isError()) {
            $this->errors[$instance->getId()] = [
                'code' => $instance->getErrorCode(),
                'message' => $instance->getErrorMessage(),
            ];
        }

        $this->response[$instance->getId()] = [
            self::RESPONSE_HEADERS => $this->formatHeaders($instance->getResponseHeaders()),
            self::RESPONSE_COOKIES => $instance->getResponseCookies(),
            self::RESPONSE_BODY => $instance->getResponse(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatHeaders(CaseInsensitiveArray $headers): array
    {
        $formatted = [];

        foreach ($headers as $key => $value) {
            $formatted[strtolower($key)] = $value;
        }

        return $formatted;
    }

    /**
     * @throws BaseException
     * @phpstan-assert Curl $this->client
     */
    private function ensureSingleRequest(): void
    {
        if ($this->isMultiRequest()) {
            throw HttpClientException::methodNotSupported(__METHOD__, MultiCurl::class);
        }
    }

    /**
     * @param array<mixed> $arguments
     */
    private function interceptCall(string $method, array $arguments): void
    {
        switch ($method) {
            case 'setHeaders':
                if (isset($arguments[0]) && is_array($arguments[0])) {
                    $this->requestHeaders = array_change_key_case($arguments[0], CASE_LOWER);
                }
                break;

            case 'setHeader':
                if (isset($arguments[0], $arguments[1])) {
                    $this->requestHeaders[strtolower($arguments[0])] = $arguments[1];
                }
                break;
        }
    }
}
