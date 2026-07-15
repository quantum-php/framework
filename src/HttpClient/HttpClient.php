<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient;

use Quantum\HttpClient\Contracts\HttpClientAdapterInterface;
use Quantum\HttpClient\Contracts\MultiCurlAdapterInterface;
use Quantum\HttpClient\Contracts\CurlAdapterInterface;
use Quantum\HttpClient\Exceptions\HttpClientException;
use Quantum\HttpClient\Adapters\MultiCurlAdapter;
use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\App\Exceptions\BaseException;
use Curl\MultiCurl;
use ErrorException;
use Curl\Curl;

/**
 * HttpClient Class
 * @package Quantum\HttpClient
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
     * @var HttpClientAdapterInterface|null
     */
    private ?HttpClientAdapterInterface $client = null;

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
     * @var array<int|string, mixed>
     */
    private array $response = [];

    /**
     * @var array<int|string, mixed>
     */
    private array $errors = [];

    /**
     * Creates request
     */
    public function createRequest(string $url, ?Curl $client = null): HttpClient
    {
        $adapter = new CurlAdapter($client);
        $adapter->setUrl($url);

        $this->client = $adapter;

        return $this;
    }

    /**
     * Creates multi request
     */
    public function createMultiRequest(?MultiCurl $client = null): HttpClient
    {
        $adapter = new MultiCurlAdapter($client);

        $adapter->complete(function (CurlAdapterInterface $instance): void {
            $this->handleResponse($instance);
        });

        $this->client = $adapter;

        return $this;
    }

    /**
     * Creates async multi request
     */
    public function createAsyncMultiRequest(callable $success, callable $error, ?MultiCurl $client = null): HttpClient
    {
        $adapter = new MultiCurlAdapter($client);

        $adapter->success($success);
        $adapter->error($error);

        $this->client = $adapter;

        return $this;
    }

    /**
     * Gets the current adapter
     */
    public function getAdapter(): ?HttpClientAdapterInterface
    {
        return $this->client;
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
     * @phpstan-assert-if-true MultiCurlAdapterInterface $this->client
     * @phpstan-assert-if-false CurlAdapterInterface|null $this->client
     */
    public function isMultiRequest(): bool
    {
        return $this->client instanceof MultiCurlAdapterInterface;
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
     * @return array<int|string, mixed>
     */
    public function getResponse(): array
    {
        if ($this->client === null) {
            return [];
        }

        if ($this->isMultiRequest()) {
            return $this->response;
        }

        return $this->response[$this->client->getId()] ?? [];
    }

    /**
     * Returns the errors
     * @return array<int|string, mixed>
     */
    public function getErrors(): array
    {
        if ($this->client === null) {
            return [];
        }

        if ($this->isMultiRequest()) {
            return $this->errors;
        }

        return $this->errors[$this->client->getId()] ?? [];
    }

    /**
     * Gets the curl info
     * @return mixed
     * @throws BaseException
     */
    public function info(?int $option = null)
    {
        $this->ensureSingleRequest();

        return $option !== null ? $this->client->getInfo($option) : $this->client->getInfo();
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
        $this->ensureRequestCreated();

        if (!$this->client->supportsMethod($method)) {
            throw HttpClientException::methodNotSupported($method, $this->client::class);
        }

        $this->interceptCall($method, $arguments);

        $this->ensureRequestCreated();

        $this->client->callMethod($method, $arguments);

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

        $this->client->start();
        $this->handleResponse($this->client);
    }

    /**
     * Handles the response
     */
    private function handleResponse(CurlAdapterInterface $instance): void
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
     * @param iterable<string, mixed> $headers
     * @return array<string, mixed>
     */
    private function formatHeaders(iterable $headers): array
    {
        $formatted = [];

        foreach ($headers as $key => $value) {
            $formatted[strtolower($key)] = $value;
        }

        return $formatted;
    }

    /**
     * @throws BaseException
     * @phpstan-assert CurlAdapterInterface $this->client
     */
    private function ensureSingleRequest(): void
    {
        $this->ensureRequestCreated();

        if ($this->isMultiRequest()) {
            throw HttpClientException::methodNotSupported(__METHOD__, MultiCurlAdapter::class);
        }
    }

    /**
     * @throws HttpClientException
     * @phpstan-assert HttpClientAdapterInterface $this->client
     */
    private function ensureRequestCreated(): void
    {
        if ($this->client === null) {
            throw HttpClientException::requestNotCreated();
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
