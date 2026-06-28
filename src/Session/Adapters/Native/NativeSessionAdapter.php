<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Session\Adapters\Native;

use Quantum\Session\Contracts\SessionStorageInterface;
use Quantum\Session\Exceptions\SessionException;
use Quantum\Session\Traits\SessionTrait;

/**
 * Class Session
 * @package Quantum\Session
 */
class NativeSessionAdapter implements SessionStorageInterface
{
    use SessionTrait;

    /**
     * Session default timeout
     */
    public const SESSION_TIMEOUT = 30 * 60;

    /**
     * Session params
     * @var array<string, mixed>|null
     */
    private static ?array $params = [];

    /**
     * Session storage
     * @var array<string, mixed>
     */
    private static array $storage = [];

    /**
     * @param array<string, mixed>|null $params
     * @throws SessionException
     */
    public function __construct(?array $params = null)
    {
        $this->initializeSession($params);
    }

    /**
     * @param array<string, mixed>|null $params
     * @throws SessionException
     */
    protected function initializeSession(?array $params = null): void
    {
        $timeout = $params['timeout'] ?? self::SESSION_TIMEOUT;

        if (session_status() !== PHP_SESSION_ACTIVE && @session_start() === false) {
            throw SessionException::sessionNotStarted();
        }

        if (isset($_SESSION['LAST_ACTIVITY']) && time() - $_SESSION['LAST_ACTIVITY'] > $timeout && @session_destroy() === false) {
            throw SessionException::sessionNotDestroyed();
        }

        $_SESSION['LAST_ACTIVITY'] = time();

        self::$params = $params;
        self::$storage = &$_SESSION;
    }
}
