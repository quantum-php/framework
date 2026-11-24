<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Session\Adapters\Database;

use Quantum\Session\Contracts\SessionStorageInterface;
use Quantum\Session\Exceptions\SessionException;
use Quantum\Model\Exceptions\ModelException;
use Quantum\Model\Factories\ModelFactory;
use Quantum\App\Exceptions\BaseException;
use Quantum\Session\Traits\SessionTrait;
use Quantum\Di\Exceptions\DiException;
use Quantum\Config\ConfigSection;
use ReflectionException;

/**
 * Class Session
 * @package Quantum\Session
 */
class DatabaseSessionAdapter implements SessionStorageInterface
{
    use SessionTrait;

    /**
     * Session default table
     */
    public const SESSION_TABLE = 'sessions';

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
     * @throws SessionException|ModelException|BaseException|DiException|ReflectionException
     */
    public function __construct(ConfigSection $config = new ConfigSection())
    {
        $this->initializeSession($config);
    }

    /**
     * @throws SessionException|ModelException|BaseException|DiException|ReflectionException
     */
    protected function initializeSession(ConfigSection $config): void
    {
        $sessionTable = $config->get('table') ?? self::SESSION_TABLE;

        $sessionModel = ModelFactory::createDynamicModel($sessionTable);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_save_handler(new DatabaseHandler($sessionModel->getOrmInstance()), true);

            if (@session_start() === false) {
                throw SessionException::sessionNotStarted();
            }
        }

        self::$config = $config;
        self::$storage = &$_SESSION;
    }
}
