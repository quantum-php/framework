<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App;

use Quantum\App\Exceptions\BaseException;
use Quantum\App\Exceptions\AppException;
use Quantum\App\Contracts\AppInterface;
use RuntimeException;

/**
 * Class App
 * @package Quantum\App
 *
 * @method ?int start()
 */
class App
{
    private static ?AppContext $context = null;

    private AppInterface $adapter;

    public function __construct(AppInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public static function setContext(AppContext $context): void
    {
        self::$context = $context;
    }

    public static function getContext(): AppContext
    {
        if (self::$context === null) {
            throw new RuntimeException('AppContext is not initialized.');
        }

        return self::$context;
    }

    public static function getBaseDir(): string
    {
        return self::getContext()->getBaseDir();
    }

    public function getAdapter(): AppInterface
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
            throw AppException::methodNotSupported($method, $this->adapter::class);
        }

        return $this->adapter->$method(...$arguments);
    }
}
