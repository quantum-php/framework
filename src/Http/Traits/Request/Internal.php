<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Request;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Enums\ContentType;
use Quantum\Environment\Server;
use ReflectionException;

/**
 * Trait Internal
 * @package Quantum\Http
 */
trait Internal
{
    /**
     * Creates an internal request for testing purposes
     * @param string $method
     * @param string $url
     * @param array<string, mixed> $params
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $files
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function create(
        string $method,
        string $url,
        array  $params = [],
        array  $headers = [],
        array  $files = []
    ): void {
        $parsed = parse_url($url);

        $server = server();

        $server->flush();

        $server->set('REQUEST_METHOD', strtoupper($method));

        $path = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '/';

        $server->set('REQUEST_URI', $path);

        if (isset($parsed['scheme'])) {
            $server->set('REQUEST_SCHEME', $parsed['scheme']);
            $server->set('HTTPS', $parsed['scheme'] === 'https' ? 'on' : 'off');
        }

        if (isset($parsed['host'])) {
            $server->set('HTTP_HOST', $parsed['host']);
            $server->set('SERVER_NAME', $parsed['host']);
        }

        if (isset($parsed['port'])) {
            $server->set('SERVER_PORT', $parsed['port']);
        } else {
            $server->set('SERVER_PORT', ($parsed['scheme'] ?? '') === 'https' ? 443 : 80);
        }

        if (isset($parsed['query'])) {
            $server->set('QUERY_STRING', $parsed['query']);
        } else {
            $server->set('QUERY_STRING', '');
        }

        $this->detectAndSetContentType($server, $params, $files);

        foreach ($headers as $name => $value) {
            $server->set('HTTP_' . strtoupper(str_replace('-', '_', $name)), $value);
        }

        $this->flush();

        $this->server = $server;
        $this->populateFromServer();

        if ($params !== []) {
            $this->setRequestParams($params);
        }

        if ($files !== []) {
            $this->setUploadedFiles($this->handleFiles($files));
        }
    }

    /**
     * Detects the content type
     * @param array<string, mixed>|null $data
     * @param array<string, mixed>|null $files
     */
    protected function detectAndSetContentType(Server $server, ?array $data = null, ?array $files = null): void
    {
        if ($files && count($files) > 0) {
            $server->set('CONTENT_TYPE', ContentType::FORM_DATA);
        } elseif ($data) {
            $server->set('CONTENT_TYPE', ContentType::URL_ENCODED);
        } else {
            $server->set('CONTENT_TYPE', ContentType::HTML);
        }
    }
}
