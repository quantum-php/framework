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
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Session\Traits\SessionTrait;
use Quantum\Config\ConfigSection;

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
     * Session config, kept to start the session again after regenerateId()
     */
    private static ?ConfigSection $config = null;

    /**
     * Session storage
     * @var array<string, mixed>
     */
    private static array $storage = [];

    /**
     * @throws ConfigException|SessionException
     */
    public function __construct(ConfigSection $config = new ConfigSection())
    {
        $this->initializeSession($config);
    }

    /**
     * @throws ConfigException|SessionException
     */
    protected function initializeSession(ConfigSection $config): void
    {
        $timeout = $config->getInt('timeout', self::SESSION_TIMEOUT);

        if (session_status() !== PHP_SESSION_ACTIVE && @session_start() === false) {
            throw SessionException::sessionNotStarted();
        }

        if (isset($_SESSION['LAST_ACTIVITY']) && time() - $_SESSION['LAST_ACTIVITY'] > $timeout && @session_destroy() === false) {
            throw SessionException::sessionNotDestroyed();
        }

        $_SESSION['LAST_ACTIVITY'] = time();

        self::$config = $config;
        self::$storage = &$_SESSION;
    }
}
