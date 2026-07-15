<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Contracts;

/**
 * Interface HttpClientAdapterInterface
 * @package Quantum\HttpClient
 */
interface HttpClientAdapterInterface
{
    /**
     * Starts request execution
     * @return mixed
     */
    public function start();

    /**
     * Sets request header
     * @param mixed $value
     */
    public function setHeader(string $key, $value): self;

    /**
     * Sets request headers
     * @param array<int|string, mixed> $headers
     */
    public function setHeaders(array $headers): self;

    /**
     * Sets request option
     * @param mixed $value
     */
    public function setOpt(int $option, $value): self;

    /**
     * Sets multiple request options
     * @param array<int, mixed> $options
     */
    public function setOpts(array $options): self;

    /**
     * Checks if adapter supports method passthrough
     */
    public function supportsMethod(string $method): bool;

    /**
     * Calls supported adapter method
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function callMethod(string $method, array $arguments);
}
