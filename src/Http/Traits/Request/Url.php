<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Request;

/**
 * Trait Url
 * @package Quantum\Http
 */
trait Url
{
    /**
     * Scheme
     */
    private ?string $__protocol = null;

    /**
     * Host name
     */
    private ?string $__host = null;

    /**
     * Server port
     */
    private ?string $__port = null;

    /**
     * Request URI
     */
    private ?string $__uri = null;

    /**
     * Gets the protocol
     * @return string
     */
    public function getProtocol(): ?string
    {
        return $this->__protocol;
    }

    /**
     * Sets the protocol
     */
    public function setProtocol(string $protocol): void
    {
        $this->__protocol = $protocol;
    }

    /**
     * Gets the host name
     * @return string
     */
    public function getHost(): ?string
    {
        return $this->__host;
    }

    /**
     * Sets the host name
     */
    public function setHost(string $host): void
    {
        $this->__host = $host;
    }

    /**
     * Gets the port
     * @return string
     */
    public function getPort(): ?string
    {
        return $this->__port;
    }

    /**
     * Sets the port
     */
    public function setPort(string $port): void
    {
        $this->__port = $port;
    }

    /**
     * Gets the URI
     */
    public function getUri(): ?string
    {
        return $this->__uri;
    }

    /**
     * Sets the URI
     */
    public function setUri(string $uri): void
    {
        $this->__uri = ltrim($uri, '/');
    }

    /**
     * Returns the URI segment at the specified index.
     */
    public function getSegment(int $index): ?string
    {
        $segments = $this->getAllSegments();

        return $segments[$index] ?? null;
    }

    /**
     * Gets all URI segments as an array.
     * @return array<string>
     */
    public function getAllSegments(): array
    {
        if ($this->__uri === null) {
            return ['zero_segment'];
        }

        $parsed = parse_url($this->__uri);
        $segments = explode('/', trim(is_array($parsed) ? ($parsed['path'] ?? '') : '', '/'));
        array_unshift($segments, 'zero_segment');
        return $segments;
    }
}
