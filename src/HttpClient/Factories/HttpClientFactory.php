<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Factories;

use Quantum\HttpClient\HttpClient;

/**
 * Class HttpClientFactory
 * @package Quantum\HttpClient
 */
class HttpClientFactory
{
    public static function createRequest(string $url): HttpClient
    {
        return (new HttpClient())->createRequest($url);
    }

    public static function createMultiRequest(): HttpClient
    {
        return (new HttpClient())->createMultiRequest();
    }

    public static function createAsyncMultiRequest(callable $success, callable $error): HttpClient
    {
        return (new HttpClient())->createAsyncMultiRequest($success, $error);
    }
}
