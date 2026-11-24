<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Session\Factories;

use Quantum\Session\Adapters\Database\DatabaseSessionAdapter;
use Quantum\Session\Adapters\Native\NativeSessionAdapter;
use Quantum\Session\Contracts\SessionStorageInterface;
use Quantum\Session\Exceptions\SessionException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Session\Enums\SessionType;
use Quantum\Session\Session;
use Quantum\Config\Setup;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class SessionFactory
 * @package Quantum\Session
 */
class SessionFactory
{
    public const ADAPTERS = [
        SessionType::NATIVE => NativeSessionAdapter::class,
        SessionType::DATABASE => DatabaseSessionAdapter::class,
    ];

    /**
     * @var array<string, Session>
     */
    private array $instances = [];

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public static function get(?string $adapter = null): Session
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve($adapter);
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function resolve(?string $adapter = null): Session
    {
        if (!config()->has('session')) {
            config()->import(new Setup('config', 'session'));
        }

        $adapter ??= config()->get('session.default');

        $adapterClass = $this->getAdapterClass($adapter);

        return $this->instances[$adapter] ??= $this->createInstance($adapterClass, $adapter);
    }

    /**
     * @throws BaseException|DiException|ReflectionException
     */
    private function createInstance(string $adapterClass, string $adapter): Session
    {
        $adapterInstance = new $adapterClass(config()->section('session.' . $adapter));

        if (!$adapterInstance instanceof SessionStorageInterface) {
            throw SessionException::adapterNotSupported($adapter);
        }

        return new Session($adapterInstance);
    }

    /**
     * @throws BaseException
     */
    private function getAdapterClass(string $adapter): string
    {
        if (!array_key_exists($adapter, self::ADAPTERS)) {
            throw SessionException::adapterNotSupported($adapter);
        }

        return self::ADAPTERS[$adapter];
    }
}
