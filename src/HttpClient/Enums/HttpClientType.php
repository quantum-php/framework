<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Enums;

/**
 * Class HttpClientType
 * @package Quantum\HttpClient
 * @codeCoverageIgnore
 */
final class HttpClientType
{
    public const CURL = 'curl';

    public const MULTI_CURL = 'multi_curl';

    private function __construct()
    {
    }
}
