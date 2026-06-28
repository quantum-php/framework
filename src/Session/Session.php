<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Session;

use Quantum\Session\Contracts\SessionStorageInterface;
use Quantum\Session\Exceptions\SessionException;
use Quantum\App\Exceptions\BaseException;

/**
 * Class Session
 * @package Quantum\Session
 * @method array<string, mixed> all()
 * @method bool has(string $key)
 * @method mixed|null get(string $key)
 * @method void set(string $key, $value)
 * @method mixed|null getFlash(string $key)
 * @method void setFlash(string $key, $value)
 * @method void delete(string $key)
 * @method void flush()
 * @method string|null getId()
 * @method bool regenerateId()
 */
class Session
{
    private SessionStorageInterface $adapter;

    public function __construct(SessionStorageInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): SessionStorageInterface
    {
        return $this->adapter;
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     * @throws BaseException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists($this->adapter, $method)) {
            throw SessionException::methodNotSupported($method, $this->adapter::class);
        }

        return $this->adapter->$method(...$arguments);
    }
}
