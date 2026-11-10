<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient;

use JsonSerializable;
use CURLFile;

/**
 * Class PostDataBuilder
 * @package Quantum\HttpClient
 */
class PostDataBuilder
{
    /**
     * @param mixed $data
     * @param array<int|string, mixed> $headers
     * @return mixed
     */
    public function build($data, array $headers)
    {
        if (
            $this->hasJsonContentType($headers) &&
            (
                is_array($data) ||
                $data instanceof JsonSerializable
            )
        ) {
            return json_encode($data) ?: '';
        }

        if (is_array($data)) {
            return $this->hasMultipartContentType($headers) || $this->hasCurlFile($data)
                ? $data
                : http_build_query($data);
        }

        return $data;
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    private function hasJsonContentType(array $headers): bool
    {
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === 'content-type') {
                return preg_match('/^application\/(?:[a-z.-]+\+)?json\b/i', (string) $value) === 1;
            }
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    private function hasMultipartContentType(array $headers): bool
    {
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === 'content-type') {
                return stripos((string) $value, 'multipart/form-data') !== false;
            }
        }

        return false;
    }

    /**
     * @param array<mixed> $data
     */
    private function hasCurlFile(array $data): bool
    {
        foreach ($data as $value) {
            if ($value instanceof CURLFile) {
                return true;
            }

            if (is_array($value) && $this->hasCurlFile($value)) {
                return true;
            }
        }

        return false;
    }
}
