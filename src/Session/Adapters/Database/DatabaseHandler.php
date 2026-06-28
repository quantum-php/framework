<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Session\Adapters\Database;

use Quantum\Database\Contracts\DbalInterface;
use SessionHandlerInterface;

/**
 * Class DatabaseHandler
 * @package Quantum\Session
 */
class DatabaseHandler implements SessionHandlerInterface
{
    private DbalInterface $sessionModel;

    /**
     * DatabaseHandler constructor.
     */
    public function __construct(DbalInterface $sessionModel)
    {
        $this->sessionModel = $sessionModel;
    }

    /**
     * @inheritDoc
     */
    public function open($path, $name): bool
    {
        return (bool) $this->sessionModel::getConnection();
    }

    /**
     * @inheritDoc
     */
    public function close(): bool
    {
        return !$this->sessionModel::getConnection();
    }

    /**
     * @inheritDoc
     */
    public function read($id): string
    {
        $result = $this->sessionModel->findOneby('session_id', $id);
        return $result->prop('data') ?: '';
    }

    /**
     * @inheritDoc
     */
    public function write($id, $data): bool
    {
        $sessionItem = $this->sessionModel->findOneBy('session_id', $id);

        if (empty($sessionItem->asArray())) {
            $sessionItem = $this->sessionModel->create();
        }

        $sessionItem->prop('session_id', $id);
        $sessionItem->prop('ttl', time());
        $sessionItem->prop('data', $data);

        return $sessionItem->save();
    }

    /**
     * @inheritDoc
     */
    public function destroy($id): bool
    {
        if ($this->sessionModel->findOneBy('session_id', $id)->asArray()) {
            return $this->sessionModel->findOneBy('session_id', $id)->delete();
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function gc($max_lifetime)
    {
        $old = time() - $max_lifetime;

        $result = $this->sessionModel->criteria('ttl', '<', $old)->deleteMany();

        if ($result === false) {
            return false;
        }

        return 0;
    }
}
