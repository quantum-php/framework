<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient;

/**
 * Class ResponseParser
 * @package Quantum\HttpClient
 */
class ResponseParser
{
    public function parseHeaders(string $rawHeaders): ResponseHeaders
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
     * @return array{name: string, value: string}|null
     */
    public function parseCookie(string $header): ?array
    {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i', $header, $cookie) !== 1) {
            return null;
        }

        return [
            'name' => $cookie[1],
            'value' => rawurldecode(trim($cookie[2], " \n\r\t\0\x0B")),
        ];
    }

    /**
     * @return mixed
     */
    public function parseBody(string $rawResponse, ResponseHeaders $headers)
    {
        $response = $rawResponse;
        $contentType = $headers['Content-Type'] ?? null;
        $contentEncoding = $headers['Content-Encoding'] ?? null;

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
