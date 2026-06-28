<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth;

use Quantum\Auth\Contracts\AuthenticatableInterface;
use Quantum\Auth\Exceptions\AuthException;
use Quantum\App\Exceptions\BaseException;

/**
 * Class Auth
 * @package Quantum\Auth
 * @method mixed signin(string $username, string $password, bool $remember = false)
 * @method bool signout()
 * @method bool check()
 * @method User user()
 */
class Auth
{
    private AuthenticatableInterface $adapter;

    public function __construct(AuthenticatableInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): AuthenticatableInterface
    {
        return $this->adapter;
    }

    /**
     * @param array<mixed>|null $arguments
     * @return mixed
     * @throws BaseException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists($this->adapter, $method)) {
            throw AuthException::methodNotSupported($method, $this->adapter::class);
        }

        return $this->adapter->$method(...$arguments);
    }

}
