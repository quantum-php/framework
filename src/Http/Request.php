<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Http\Exceptions\HttpException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Http\Traits\Request\RawInput;
use Quantum\Http\Traits\Request\Internal;
use Quantum\Http\Traits\Request\Header;
use Quantum\Http\Traits\Request\Params;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Traits\Request\Query;
use Quantum\Http\Traits\Request\Route;
use Quantum\Http\Traits\Request\Body;
use Quantum\Http\Traits\Request\File;
use Quantum\Http\Traits\Request\Url;
use Quantum\Environment\Server;
use ReflectionException;
use Quantum\Csrf\Csrf;

/**
 * Class Request
 * @package Quantum\Http
 */
class Request
{
    use Route;
    use Header;
    use Body;
    use Url;
    use Query;
    use Params;
    use File;
    use RawInput;
    use Internal;

    /**
     * Available methods
     */
    public const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Default port for HTTP
     */
    public const DEFAULT_HTTP_PORT = 80;

    /**
     * Default port for HTTPS
     */
    public const DEFAULT_HTTPS_PORT = 443;

    /**
     * Request method
     */
    private ?string $__method = null;

    protected Server $server;

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function __construct(?Server $server = null)
    {
        $this->server = $server ?? server();
        $this->populateFromServer();
    }

    /**
     * Flushes the request header, body and files
     */
    public function flush(): void
    {
        $this->__headers = [];
        $this->__request = [];
        $this->__files = [];
        $this->__protocol = null;
        $this->__host = null;
        $this->__port = null;
        $this->__uri = null;
        $this->__query = null;
    }

    /**
     * Re-reads method, headers, params and files from the current server state.
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    protected function populateFromServer(): void
    {
        $this->setServerInfo();
        $this->setContentType();
        $this->setRequestHeaders();

        ['params' => $rawInputParams, 'files' => $rawInputFiles] = $this->getRawInputParams();

        $this->setRequestParams($rawInputParams);
        $this->setUploadedFiles($rawInputFiles);
    }

    /**
     * Sets the merged request parameters
     * @param array<string, mixed> $params
     */
    public function setRequestParams(array $params): void
    {
        $this->__request = array_merge(
            $this->getParams(),
            $this->postParams(),
            $this->jsonPayloadParams(),
            $this->urlEncodedParams(),
            $params
        );
    }

    /**
     * Sets the uploaded files array merging handled $_FILES and parsed files
     * @param array<string, mixed> $files
     * @throws BaseException
     * @throws ReflectionException
     */
    public function setUploadedFiles(array $files): void
    {
        $this->__files = array_merge(
            $this->handleFiles($_FILES),
            $files
        );
    }

    /**
     * Gets the request method
     */
    public function getMethod(): ?string
    {
        return $this->__method;
    }

    /**
     * Sets the request method
     * @throws BaseException
     */
    public function setMethod(string $method): void
    {
        if (!in_array(strtoupper($method), self::METHODS)) {
            throw HttpException::requestMethodNotAvailable($method);
        }

        $this->__method = $method;
    }

    /**
     * Checks if the current method matches the given method
     */
    public function isMethod(string $method): bool
    {
        return strcasecmp($method, $this->__method ?? '') === 0;
    }

    /**
     * Gets Cross Site Request Forgery Token
     */
    public function getCsrfToken(): ?string
    {
        $csrfToken = null;

        if ($this->has(Csrf::TOKEN_KEY)) {
            $csrfToken = (string) $this->get(Csrf::TOKEN_KEY);
        } elseif ($this->hasHeader('X-' . Csrf::TOKEN_KEY)) {
            $csrfToken = $this->getHeader('X-' . Csrf::TOKEN_KEY);
        }

        return $csrfToken;
    }

    /**
     * Gets the base url
     * @throws DiException|ReflectionException
     */
    public function getBaseUrl(bool $withModulePrefix = false): string
    {
        $baseUrl = config()->get('app.base_url');

        $prefix = $this->getRoutePrefix();
        $modulePrefix = ($withModulePrefix && !in_array($prefix, [null, '', '0'], true)) ? '/' . $prefix : '';

        if ($baseUrl) {
            return $baseUrl . $modulePrefix;
        }

        return $this->getHostPrefix() . $modulePrefix;
    }

    /**
     * Gets the current url
     */
    public function getCurrentUrl(): string
    {
        $uri = $this->getUri();
        $query = $this->getQuery();
        $queryPart = $query ? '?' . $query : '';

        return $this->getHostPrefix() . '/' . $uri . $queryPart;
    }

    /**
     * Gets the protocol, host, and optional port part of the URL.
     */
    private function getHostPrefix(): string
    {
        $protocol = $this->getProtocol();
        $host = $this->getHost();
        $port = $this->getPort();

        $defaultPort = $protocol === 'https' ? self::DEFAULT_HTTPS_PORT : self::DEFAULT_HTTP_PORT;

        $portPart = ($port && $port != $defaultPort) ? ':' . $port : '';

        return $protocol . '://' . $host . $portPart;
    }

    /**
     * Sets server data (method, protocol, host, port, uri, query).
     */
    private function setServerInfo(): void
    {
        foreach (['method', 'protocol', 'host', 'port', 'uri', 'query'] as $name) {
            $this->{"__{$name}"} = $this->server->$name();
        }
    }

    /**
     * Sets the normalized request content type.
     */
    private function setContentType(): void
    {
        $this->__contentType = $this->server->contentType(true);
    }

    /**
     * Sets request headers, normalizing keys to lowercase.
     * @throws DiException|ReflectionException
     */
    private function setRequestHeaders(): void
    {
        $this->__headers = array_change_key_case(getallheaders());
    }
}
