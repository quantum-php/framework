<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Contracts;

/**
 * Interface CurlAdapterInterface
 * @package Quantum\HttpClient
 */
interface CurlAdapterInterface extends HttpClientAdapterInterface
{
    /**
     * Sets request URL
     */
    public function setUrl(string $url): self;

    /**
     * Builds post data
     * @param mixed $data
     * @return mixed
     */
    public function buildPostData($data);

    /**
     * Gets request id
     * @return int|string
     */
    public function getId();

    /**
     * Checks if request has error
     */
    public function isError(): bool;

    /**
     * Gets error code
     */
    public function getErrorCode(): int;

    /**
     * Gets error message
     */
    public function getErrorMessage(): ?string;

    /**
     * Gets response headers
     * @return iterable<string, mixed>
     */
    public function getResponseHeaders(): iterable;

    /**
     * Gets response cookies
     * @return mixed
     */
    public function getResponseCookies();

    /**
     * Gets response
     * @return mixed
     */
    public function getResponse();

    /**
     * Gets curl info
     * @return mixed
     */
    public function getInfo(?int $option = null);

    /**
     * Gets request URL
     */
    public function getUrl(): ?string;
}
