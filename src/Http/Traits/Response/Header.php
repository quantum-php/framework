<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Response;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\App\Enums\ReservedKeys;
use Quantum\Http\Enums\ContentType;
use Quantum\Http\Request;
use ReflectionException;

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

    /**
     * Redirects back to the referrer when it is on the application origin,
     * falls back to the base url otherwise
     * @param array<string, mixed> $data Old input to keep for the next request
     * @return self
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function back(array $data = [], int $code = 302): self
    {
        if ($data !== []) {
            session()->set(ReservedKeys::PREV_REQUEST->value, $data);
        }

        $referrer = get_referrer();

        return $this->redirect($referrer && $this->isSameOrigin($referrer) ? $referrer : base_url(), $code);
    }

    /**
     * Checks that the url has the scheme, host and port of the base url
     * @throws DiException|ReflectionException
     */
    private function isSameOrigin(string $url): bool
    {
        $target = parse_url($url);
        $base = parse_url(base_url());

        if (str_contains($url, '\\') || !is_array($target) || !is_array($base) || isset($target['user'])) {
            return false;
        }

        $origin = $this->getOrigin($target);

        return $origin !== null && $origin === $this->getOrigin($base);
    }

    /**
     * Gets the scheme, host and port of the parsed url
     * @param array<string, mixed> $parts
     */
    private function getOrigin(array $parts): ?string
    {
        if (!isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        $port = $parts['port'] ?? ($scheme === 'https' ? Request::DEFAULT_HTTPS_PORT : Request::DEFAULT_HTTP_PORT);

        return $scheme . '://' . strtolower($parts['host']) . ':' . $port;
    }
}
