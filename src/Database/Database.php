<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database;

use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Database\Adapters\Sleekdb\SleekDbal;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Database\Traits\TransactionTrait;
use Quantum\Database\Traits\RelationalTrait;
use Quantum\App\Exceptions\BaseException;
use Quantum\Database\Enums\DatabaseType;
use Quantum\Di\Exceptions\DiException;
use Quantum\Loader\Setup;
use ReflectionException;

/**
 * Class Database
 * @package Quantum\Database
 */
class Database
{
    use TransactionTrait;
    use RelationalTrait;

    public const ADAPTERS = [
        DatabaseType::SLEEKDB => SleekDbal::class,
        DatabaseType::MYSQL => IdiormDbal::class,
        DatabaseType::SQLITE => IdiormDbal::class,
        DatabaseType::PGSQL => IdiormDbal::class,
    ];

    /**
     * Database configurations
     * @var array<string, mixed>
     */
    private $configs;

    private string $ormClass;

    /**
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function __construct()
    {
        if (!config()->has('database')) {
            config()->import(new Setup('config', 'database'));
        }

        $adapter = config()->get('database.default');

        $this->ormClass = $this->getAdapterClass($adapter);

        $this->configs = config()->get('database.' . $adapter);

        if (!$this->ormClass::getConnection()) {
            $this->ormClass::connect($this->configs);
        }
    }

    /**
     * Gets the ORM class
     */
    public function getOrmClass(): string
    {
        return $this->ormClass;
    }

    /**
     * Gets DB configurations
     * @return array<string, mixed>|null
     */
    public function getConfigs(): ?array
    {
        return $this->configs;
    }

    /**
     * @throws BaseException
     */
    private function getAdapterClass(string $adapter): string
    {
        if (!array_key_exists($adapter, self::ADAPTERS)) {
            throw DatabaseException::adapterNotSupported($adapter);
        }

        return self::ADAPTERS[$adapter];
    }
}
