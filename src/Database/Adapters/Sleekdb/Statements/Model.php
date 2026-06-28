<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Sleekdb\Statements;

use SleekDB\Exceptions\InvalidConfigurationException;
use Quantum\Database\Exceptions\DatabaseException;
use SleekDB\Exceptions\InvalidArgumentException;
use Quantum\Database\Contracts\DbalInterface;
use SleekDB\Exceptions\IdNotAllowedException;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use SleekDB\Exceptions\JsonException;
use SleekDB\Exceptions\IOException;

/**
 * Trait Model
 * @package Quantum\Database
 */
trait Model
{
    /**
     * @inheritDoc
     */
    public function create(): DbalInterface
    {
        $this->isNew = true;
        $this->modifiedFields = [];
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function prop(string $key, $value = null)
    {
        if (func_num_args() === 2) {
            $this->modifiedFields[$key] = $value;
        } else {
            return $this->modifiedFields[$key] ?? null;
        }
        return null;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     * @throws IOException
     * @throws IdNotAllowedException
     * @throws InvalidArgumentException
     * @throws InvalidConfigurationException
     * @throws JsonException|BaseException
     */
    public function save(): bool
    {
        $ormMode = $this->getOrmModel();

        if ($this->isNew) {
            $ormMode->insert($this->modifiedFields);
        } else {
            $ormMode->update($this->modifiedFields);
        }

        return true;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws InvalidConfigurationException|BaseException
     */
    public function delete(): bool
    {
        $pk = $this->getOrmModel()->getPrimaryKey();
        return $this->getOrmModel()->deleteById($this->data[$pk]);
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws InvalidConfigurationException
     * @throws ModelException|BaseException
     */
    public function deleteMany(): bool
    {
        $result = $this->getBuilder()->getQuery()->delete();
        return is_bool($result) ? $result : $result !== 0;
    }
}
