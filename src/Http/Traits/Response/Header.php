<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Response;

use Quantum\Http\Enums\ContentType;

/**
 * Trait Header
 * @package Quantum\Http
 */
trait Header
{
    /**
     * Response headers
     * @var array<string, mixed>
     */
    private array $__headers = [];

    /**
     * Checks the response header existence by given key
     */
    public function hasHeader(string $key): bool
    {
        return isset($this->__headers[$key]);
    }

    /**
     * Gets the response header by given key
     */
    public function getHeader(string $key): ?string
    {
        return $this->hasHeader($key) ? $this->__headers[$key] : null;
    }

    /**
     * Sets the response header
     */
    public function setHeader(string $key, string $value): self
    {
        $this->__headers[$key] = $value;
        return $this;
    }

    /**
     * Get all response headers
     * @return array<string, mixed>
     */
    public function allHeaders(): array
    {
        return $this->__headers;
    }

    /**
     * Deletes the header by given key
     */
    public function deleteHeader(string $key): void
    {
        if ($this->hasHeader($key)) {
            unset($this->__headers[$key]);
        }
    }

    /**
     * Sets the content type
     */
    public function setContentType(string $contentType): self
    {
        return $this->setHeader('Content-Type', $contentType);
    }

    /**
     * Gets the content type
     */
    public function getContentType(): string
    {
        return $this->getHeader('Content-Type') ?? ContentType::HTML;
    }

    /**
     * Redirect
     * @return self
     */
    public function redirect(string $url, int $code = 302): self
    {
        $this->setStatusCode($code);
        $this->setHeader('Location', $url);
        return $this;
    }
}
