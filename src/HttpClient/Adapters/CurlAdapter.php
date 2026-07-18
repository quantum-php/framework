<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Adapters;

use Quantum\HttpClient\Contracts\CurlAdapterInterface;
use Curl\CaseInsensitiveArray;
use JsonSerializable;
use SimpleXMLElement;
use RuntimeException;
use CurlHandle;
use Curl\Curl;

/**
 * Class CurlAdapter
 * @package Quantum\HttpClient
 */
class CurlAdapter implements CurlAdapterInterface
{
    private static int $lastId = 0;

    private ?Curl $client;

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

    private CaseInsensitiveArray $responseHeaders;

    /**
     * @var array<string, mixed>
     */
    private array $responseCookies = [];

    private bool $error = false;

    private int $errorCode = 0;

    private ?string $errorMessage = null;

    /**
     * The injected vendor client is a temporary bridge for MultiCurlAdapter until #567.
     */
    public function __construct(?Curl $client = null)
    {
        $this->client = $client;
        $this->id = self::$lastId++;

        $handle = curl_init();

        if (!$handle instanceof CurlHandle) {
            throw new RuntimeException('Unable to initialize curl handle');
        }

        $this->handle = $handle;
        $this->responseHeaders = new CaseInsensitiveArray();
        $this->applyOption(CURLOPT_RETURNTRANSFER, true);
        $this->applyOption(CURLOPT_HEADER, false);
        $this->applyOption(CURLOPT_HEADERFUNCTION, function ($handle, string $header): int {
            $this->rawResponseHeaders .= $header;
            $this->parseCookieHeader($header);

            return strlen($header);
        });
    }

    public function __destruct()
    {
        curl_close($this->handle);
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
        if (
            $this->hasJsonContentType() &&
            (
                is_array($data) ||
                $data instanceof JsonSerializable
            )
        ) {
            return json_encode($data) ?: '';
        }

        return is_array($data) ? http_build_query($data) : $data;
    }

    private function applyHeaders(): void
    {
        $headers = [];

        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        $this->applyOption(CURLOPT_HTTPHEADER, $headers);
    }

    private function hasJsonContentType(): bool
    {
        foreach ($this->headers as $key => $value) {
            if (strtolower((string) $key) === 'content-type') {
                return preg_match('/^application\/(?:[a-z.-]+\+)?json\b/i', (string) $value) === 1;
            }
        }

        return false;
    }

    public function start(): void
    {
        if ($this->client !== null) {
            $this->client->exec();
            return;
        }

        $this->resetResponseState();

        $rawResponse = curl_exec($this->handle);
        $curlErrorCode = curl_errno($this->handle);
        $curlErrorMessage = curl_error($this->handle);
        $httpStatusCode = (int) $this->getInfo(CURLINFO_HTTP_CODE);

        $this->responseHeaders = $this->parseResponseHeaders($this->rawResponseHeaders);
        $this->response = $this->parseResponse(is_string($rawResponse) ? $rawResponse : '');

        $this->error = $curlErrorCode !== 0 || in_array((int) floor($httpStatusCode / 100), [4, 5], true);
        $this->errorCode = $this->error ? ($curlErrorCode !== 0 ? $curlErrorCode : $httpStatusCode) : 0;
        $this->errorMessage = $curlErrorCode !== 0
            ? trim(curl_strerror($curlErrorCode) . ($curlErrorMessage !== '' ? ': ' . $curlErrorMessage : ''))
            : ($this->responseHeaders['Status-Line'] ?? '');
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->client !== null ? $this->client->getId() : $this->id;
    }

    public function isError(): bool
    {
        return $this->client !== null ? $this->client->isError() : $this->error;
    }

    public function getErrorCode(): int
    {
        return $this->client !== null ? $this->client->getErrorCode() : $this->errorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->client !== null ? $this->client->getErrorMessage() : $this->errorMessage;
    }

    /**
     * @return iterable<string, mixed>
     */
    public function getResponseHeaders(): iterable
    {
        return $this->client !== null ? $this->client->getResponseHeaders() : $this->responseHeaders;
    }

    /**
     * @return mixed
     */
    public function getResponseCookies()
    {
        return $this->client !== null ? $this->client->getResponseCookies() : $this->responseCookies;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->client !== null ? $this->client->getResponse() : $this->response;
    }

    /**
     * @return mixed
     */
    public function getInfo(?int $option = null)
    {
        if ($this->client !== null) {
            return $option !== null ? $this->client->getInfo($option) : $this->client->getInfo();
        }

        return $option !== null ? curl_getinfo($this->handle, $option) : curl_getinfo($this->handle);
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function supportsMethod(string $method): bool
    {
        return in_array($method, ['setHeader', 'setHeaders', 'setOpt', 'setOpts'], true)
            || ($this->client !== null && method_exists($this->client, $method));
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function callMethod(string $method, array $arguments)
    {
        if (in_array($method, ['setHeader', 'setHeaders', 'setOpt', 'setOpts'], true)) {
            return $this->$method(...$arguments);
        }

        if ($this->client === null) {
            return null;
        }

        return $this->client->$method(...$arguments);
    }

    private function resetResponseState(): void
    {
        $this->rawResponseHeaders = '';
        $this->response = null;
        $this->responseHeaders = new CaseInsensitiveArray();
        $this->responseCookies = [];
        $this->error = false;
        $this->errorCode = 0;
        $this->errorMessage = null;
    }

    private function parseCookieHeader(string $header): void
    {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/i', $header, $cookie) === 1) {
            $this->responseCookies[$cookie[1]] = rawurldecode(trim($cookie[2], " \n\r\t\0\x0B"));
        }
    }

    private function parseResponseHeaders(string $rawHeaders): CaseInsensitiveArray
    {
        $headerBlocks = explode("\r\n\r\n", trim($rawHeaders));
        $responseHeader = '';

        for ($i = count($headerBlocks) - 1; $i >= 0; $i--) {
            if (isset($headerBlocks[$i]) && stripos($headerBlocks[$i], 'HTTP/') === 0) {
                $responseHeader = $headerBlocks[$i];
                break;
            }
        }

        $headers = new CaseInsensitiveArray();
        $rawLines = preg_split('/\r\n/', $responseHeader, -1, PREG_SPLIT_NO_EMPTY);

        if ($rawLines === false || $rawLines === []) {
            return $headers;
        }

        $headers['Status-Line'] = $rawLines[0];

        for ($i = 1, $count = count($rawLines); $i < $count; $i++) {
            if (!str_contains($rawLines[$i], ':')) {
                continue;
            }

            [$key, $value] = array_pad(explode(':', $rawLines[$i], 2), 2, '');
            $key = trim($key);
            $value = trim($value);

            if (isset($headers[$key])) {
                $headers[$key] .= ',' . $value;
            } else {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /**
     * @return mixed
     */
    private function parseResponse(string $rawResponse)
    {
        $response = $rawResponse;
        $contentType = $this->responseHeaders['Content-Type'] ?? null;

        if (is_string($contentType) && preg_match('/\bjson\b/i', $contentType) === 1) {
            $decoded = json_decode($rawResponse);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $response;
        }

        if (is_string($contentType) && preg_match('/\bxml\b/i', $contentType) === 1) {
            $xml = simplexml_load_string($rawResponse);
            return $xml instanceof SimpleXMLElement ? $xml : $response;
        }

        if (($this->responseHeaders['Content-Encoding'] ?? null) === 'gzip') {
            $decoded = gzdecode($rawResponse);
            return $decoded !== false ? $decoded : $response;
        }

        return $response;
    }
}
