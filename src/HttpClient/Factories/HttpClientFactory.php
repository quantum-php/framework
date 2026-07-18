<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Factories;

use Quantum\HttpClient\HttpClient;
use Curl\MultiCurl;
use Curl\Curl;

/**
 * Class HttpClientFactory
 * @package Quantum\HttpClient
 */
class HttpClientFactory
{
    public static function createRequest(string $url, ?Curl $client = null): HttpClient
    {
        return (new HttpClient())->createRequest($url, $client);
    }

    public static function createMultiRequest(?MultiCurl $client = null): HttpClient
    {
        return (new HttpClient())->createMultiRequest($client);
    }

    public static function createAsyncMultiRequest(callable $success, callable $error, ?MultiCurl $client = null): HttpClient
    {
        return (new HttpClient())->createAsyncMultiRequest($success, $error, $client);
    }
}
