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

        $this->responseHeaders = $this->parseResponseHeaders($this->rawResponseHeaders);
        $this->response = $this->parseResponse(is_string($rawResponse) ? $rawResponse : '');

        $this->error = $curlErrorCode !== 0 || in_array((int) floor($httpStatusCode / 100), [4, 5], true);
        $this->errorCode = $this->error ? ($curlErrorCode !== 0 ? $curlErrorCode : $httpStatusCode) : 0;
        $this->errorMessage = $this->error
            ? (
                $curlErrorCode !== 0
                    ? trim(curl_strerror($curlErrorCode) . ($curlErrorMessage !== '' ? ': ' . $curlErrorMessage : ''))
                    : ($this->responseHeaders['Status-Line'] ?? '')
            )
            : null;
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

    private function parseCookieHeader(string $header): void
    {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i', $header, $cookie) === 1) {
            $this->responseCookies[$cookie[1]] = rawurldecode(trim($cookie[2], " \n\r\t\0\x0B"));
        }
    }

    private function parseResponseHeaders(string $rawHeaders): ResponseHeaders
    {
        $headerBlocks = explode("\r\n\r\n", trim($rawHeaders));
        $responseHeader = '';

        for ($i = count($headerBlocks) - 1; $i >= 0; $i--) {
            if (isset($headerBlocks[$i]) && stripos($headerBlocks[$i], 'HTTP/') === 0) {
                $responseHeader = $headerBlocks[$i];
                break;
            }
        }

        $headers = new ResponseHeaders();
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
        $contentEncoding = $this->responseHeaders['Content-Encoding'] ?? null;

        if (is_string($contentEncoding) && strtolower($contentEncoding) === 'gzip') {
            $decoded = gzdecode($rawResponse);
            $response = $decoded !== false ? $decoded : $response;
        }

        if (is_string($contentType) && preg_match('/\bjson\b/i', $contentType) === 1) {
            $decoded = json_decode($response);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $response;
        }

        if (is_string($contentType) && preg_match('/\bxml\b/i', $contentType) === 1) {
            $previousUseInternalErrors = libxml_use_internal_errors(true);

            try {
                $xml = simplexml_load_string($response);
            } finally {
                libxml_use_internal_errors($previousUseInternalErrors);
            }

            return $xml !== false ? $xml : $response;
        }

        return $response;
    }
}
