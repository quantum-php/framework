<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Factories;

use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\HttpClient\HttpClient;
use Quantum\Di\Di;
use ReflectionException;

/**
 * Class HttpClientFactory
 * @package Quantum\HttpClient
 */
class HttpClientFactory
{
    private ?HttpClient $instance = null;

    /**
     * @throws DiException|BaseException|ReflectionException
     */
    public static function get(): HttpClient
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve();
    }

    public function resolve(): HttpClient
    {
        if (!$this->instance) {
            $this->instance = $this->createInstance();
        }

        return $this->instance;
    }

    private function createInstance(): HttpClient
    {
        return new HttpClient();
    }
}
