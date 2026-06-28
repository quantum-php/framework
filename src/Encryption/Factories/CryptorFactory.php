<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Encryption\Factories;

use Quantum\Encryption\Adapters\AsymmetricEncryptionAdapter;
use Quantum\Encryption\Adapters\SymmetricEncryptionAdapter;
use Quantum\Encryption\Exceptions\CryptorException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Encryption\Enums\CryptorType;
use Quantum\Encryption\Cryptor;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class Cryptor
 * @package Quantum\Encryption
 */
class CryptorFactory
{
    public const ADAPTERS = [
        CryptorType::SYMMETRIC => SymmetricEncryptionAdapter::class,
        CryptorType::ASYMMETRIC => AsymmetricEncryptionAdapter::class,
    ];

    /**
     * @var array<string, Cryptor>
     */
    private array $instances = [];

    /**
     * @throws BaseException|ReflectionException
     */
    public static function get(string $type = CryptorType::SYMMETRIC): Cryptor
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve($type);
    }

    /**
     * @throws BaseException
     */
    public function resolve(string $type = CryptorType::SYMMETRIC): Cryptor
    {
        if (!isset($this->instances[$type])) {
            $this->instances[$type] = $this->createInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @throws BaseException
     */
    private function createInstance(string $type): Cryptor
    {
        if (!isset(self::ADAPTERS[$type])) {
            throw CryptorException::adapterNotSupported($type);
        }

        $adapterClass = self::ADAPTERS[$type];

        return new Cryptor(new $adapterClass());
    }
}
