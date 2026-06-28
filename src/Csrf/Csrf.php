<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Csrf;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Csrf\Exceptions\CsrfException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Session\Session;
use Quantum\Hasher\Hasher;
use Quantum\Http\Request;
use ReflectionException;

/**
 * Class Csrf
 * @package Quantum\Csrf
 */
class Csrf
{
    /**
     * Request methods to validate against
     */
    public const METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Csrf token key
     */
    public const TOKEN_KEY = 'csrf-token';

    private Session $storage;

    private Hasher $hasher;

    /**
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     * @throws BaseException
     */
    public function __construct()
    {
        $this->storage = session();
        $this->hasher = new Hasher();
    }

    /**
     * Generates the CSRF token or returns the previously generated one
     */
    public function generateToken(string $key): ?string
    {
        if (!$this->storage->has(self::TOKEN_KEY)) {
            $this->storage->set(self::TOKEN_KEY, $this->hasher->hash($key));
        }

        return $this->storage->get(self::TOKEN_KEY);
    }

    /**
     * Checks the token
     * @throws CsrfException
     */
    public function checkToken(Request $request): bool
    {
        if (!$request->has(self::TOKEN_KEY)) {
            throw CsrfException::tokenNotFound();
        }

        if ($this->storage->get(self::TOKEN_KEY) !== $request->getCsrfToken()) {
            throw CsrfException::tokenNotMatched();
        }

        $this->storage->delete(self::TOKEN_KEY);
        $request->delete(self::TOKEN_KEY);
        $request->deleteHeader('X-' . self::TOKEN_KEY);

        return true;
    }
}
