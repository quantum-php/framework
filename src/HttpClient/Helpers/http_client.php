<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\HttpClient\Factories\HttpClientFactory;
use Quantum\HttpClient\HttpClient;

/**
 * Creates single HTTP request client
 */
function httpRequest(string $url): HttpClient
{
    return HttpClientFactory::createRequest($url);
}

/**
 * Creates multi HTTP request client
 */
function httpMultiRequest(): HttpClient
{
    return HttpClientFactory::createMultiRequest();
}

/**
 * Creates async multi HTTP request client
 */
function httpAsyncMultiRequest(callable $success, callable $error): HttpClient
{
    return HttpClientFactory::createAsyncMultiRequest($success, $error);
}
