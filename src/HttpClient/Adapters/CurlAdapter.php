<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Adapters;

use Quantum\HttpClient\Contracts\CurlAdapterInterface;
use Curl\Curl;

/**
 * Class CurlAdapter
 * @package Quantum\HttpClient
 */
class CurlAdapter implements CurlAdapterInterface
{
    private Curl $client;

    public function __construct(?Curl $client = null)
    {
        $this->client = $client ?? new Curl();
    }

    public function setUrl(string $url): CurlAdapterInterface
    {
        $this->client->setUrl($url);

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setOpt(int $option, $value): CurlAdapterInterface
    {
        $this->client->setOpt($option, $value);

        return $this;
    }

    /**
     * @param array<int, mixed> $options
     */
    public function setOpts(array $options): CurlAdapterInterface
    {
        $this->client->setOpts($options);

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setHeader(string $key, $value): CurlAdapterInterface
    {
        $this->client->setHeader($key, $value);

        return $this;
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    public function setHeaders(array $headers): CurlAdapterInterface
    {
        $this->client->setHeaders($headers);

        return $this;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function buildPostData($data)
    {
        return $this->client->buildPostData($data);
    }

    public function start(): void
    {
        $this->client->exec();
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->client->getId();
    }

    public function isError(): bool
    {
        return $this->client->isError();
    }

    public function getErrorCode(): int
    {
        return $this->client->getErrorCode();
    }

    public function getErrorMessage(): ?string
    {
        return $this->client->getErrorMessage();
    }

    /**
     * @return iterable<string, mixed>
     */
    public function getResponseHeaders(): iterable
    {
        return $this->client->getResponseHeaders();
    }

    /**
     * @return mixed
     */
    public function getResponseCookies()
    {
        return $this->client->getResponseCookies();
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->client->getResponse();
    }

    /**
     * @return mixed
     */
    public function getInfo(?int $option = null)
    {
        return $option !== null ? $this->client->getInfo($option) : $this->client->getInfo();
    }

    public function getUrl(): ?string
    {
        return $this->client->getUrl();
    }

    public function supportsMethod(string $method): bool
    {
        return method_exists($this->client, $method);
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function callMethod(string $method, array $arguments)
    {
        return $this->client->$method(...$arguments);
    }
}
