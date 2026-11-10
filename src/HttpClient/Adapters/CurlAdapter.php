<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Adapters;

use Quantum\HttpClient\Contracts\CurlAdapterInterface;
use Quantum\HttpClient\Traits\AdapterTrait;
use Quantum\HttpClient\ResponseHeaders;
use Quantum\HttpClient\PostDataBuilder;
use Quantum\HttpClient\ResponseParser;
use RuntimeException;
use CurlHandle;

/**
 * Class CurlAdapter
 * @package Quantum\HttpClient
 */
class CurlAdapter implements CurlAdapterInterface
{
    use AdapterTrait;

    private const SUPPORTED_METHODS = ['setHeader', 'setHeaders', 'setOpt', 'setOpts'];

    private static int $lastId = 0;

    private CurlHandle $handle;

    private int $id;

    private ?string $url = null;

    /**
     * @var array<int|string, mixed>
     */
    private array $headers = [];

    private string $rawResponseHeaders = '';

    /**
     * @var mixed|null
     */
    private $response;

    private ResponseHeaders $responseHeaders;

    private PostDataBuilder $postDataBuilder;

    private ResponseParser $responseParser;

    /**
     * @var array<string, mixed>
     */
    private array $responseCookies = [];

    private bool $error = false;

    private int $errorCode = 0;

    private ?string $errorMessage = null;

    public function __construct()
    {
        $this->id = self::$lastId++;

        $handle = curl_init();

        if (!$handle instanceof CurlHandle) {
            throw new RuntimeException('Unable to initialize curl handle');
        }

        $this->handle = $handle;
        $this->responseHeaders = new ResponseHeaders();
        $this->postDataBuilder = new PostDataBuilder();
        $this->responseParser = new ResponseParser();
        $this->applyDefaultOptions();
    }

    public function __destruct()
    {
        curl_close($this->handle);
    }

    private function applyDefaultOptions(): void
    {
        $this->applyOption(CURLOPT_RETURNTRANSFER, true);
        $this->applyOption(CURLOPT_HEADER, false);
        $this->applyOption(CURLOPT_HEADERFUNCTION, function ($handle, string $header): int {
            $this->rawResponseHeaders .= $header;
            $this->parseCookieHeader($header);

            return strlen($header);
        });
    }

    /**
     * @param mixed $value
     */
    private function applyOption(int $option, $value): void
    {
        curl_setopt($this->handle, $option, $value);
    }

    public function setUrl(string $url): CurlAdapterInterface
    {
        $this->url = $url;
        $this->applyOption(CURLOPT_URL, $url);

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setOpt(int $option, $value): CurlAdapterInterface
    {
        $this->applyOption($option, $value);

        return $this;
    }

    /**
     * @param array<int, mixed> $options
     */
    public function setOpts(array $options): CurlAdapterInterface
    {
        foreach ($options as $option => $value) {
            $this->setOpt($option, $value);
        }

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setHeader(string $key, $value): CurlAdapterInterface
    {
        $this->headers[$key] = $value;
        $this->applyHeaders();

        return $this;
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    public function setHeaders(array $headers): CurlAdapterInterface
    {
        foreach ($headers as $key => $value) {
            $this->headers[trim((string) $key)] = trim((string) $value);
        }

        $this->applyHeaders();

        return $this;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function buildPostData($data)
    {
        return $this->postDataBuilder->build($data, $this->headers);
    }

    private function applyHeaders(): void
    {
        $headers = [];

        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        $this->applyOption(CURLOPT_HTTPHEADER, $headers);
    }

    public function start(): void
    {
        $this->resetResponseState();

        $rawResponse = curl_exec($this->handle);

        $this->finalizeResponse($rawResponse);
    }

    /**
     * @param mixed $rawResponse
     */
    public function finalizeResponse($rawResponse): void
    {
        $curlErrorCode = curl_errno($this->handle);
        $curlErrorMessage = curl_error($this->handle);
        $httpStatusCode = (int) $this->getInfo(CURLINFO_HTTP_CODE);

        $this->responseHeaders = $this->responseParser->parseHeaders($this->rawResponseHeaders);
        $this->response = $this->responseParser->parseBody(
            is_string($rawResponse) ? $rawResponse : '',
            $this->responseHeaders
        );

        $this->applyErrorState($curlErrorCode, $curlErrorMessage, $httpStatusCode);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function isError(): bool
    {
        return $this->error;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @return iterable<string, mixed>
     */
    public function getResponseHeaders(): iterable
    {
        return $this->responseHeaders;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResponseCookies(): array
    {
        return $this->responseCookies;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return mixed
     */
    public function getInfo(?int $option = null)
    {
        return $option !== null ? curl_getinfo($this->handle, $option) : curl_getinfo($this->handle);
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getHandle(): CurlHandle
    {
        return $this->handle;
    }

    private function resetResponseState(): void
    {
        $this->rawResponseHeaders = '';
        $this->response = null;
        $this->responseHeaders = new ResponseHeaders();
        $this->responseCookies = [];
        $this->error = false;
        $this->errorCode = 0;
        $this->errorMessage = null;
    }

    private function applyErrorState(int $curlErrorCode, string $curlErrorMessage, int $httpStatusCode): void
    {
        if ($curlErrorCode !== 0) {
            $message = (string) curl_strerror($curlErrorCode);

            if ($curlErrorMessage !== '') {
                $message .= ': ' . $curlErrorMessage;
            }

            $this->error = true;
            $this->errorCode = $curlErrorCode;
            $this->errorMessage = trim($message);

            return;
        }

        if (in_array((int) floor($httpStatusCode / 100), [4, 5], true)) {
            $this->error = true;
            $this->errorCode = $httpStatusCode;
            $this->errorMessage = $this->responseHeaders['Status-Line'] ?? '';

            return;
        }

        $this->error = false;
        $this->errorCode = 0;
        $this->errorMessage = null;
    }

    private function parseCookieHeader(string $header): void
    {
        $cookie = $this->responseParser->parseCookie($header);

        if ($cookie !== null) {
            $this->responseCookies[$cookie['name']] = $cookie['value'];
        }
    }
}
