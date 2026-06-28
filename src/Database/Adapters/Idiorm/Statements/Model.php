<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Idiorm\Statements;

use Quantum\App\Exceptions\BaseException;
use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Database\Contracts\DbalInterface;

/**
 * Trait Model
 * @package Quantum\Database
 */
trait Model
{
    /**
     * @inheritDoc
     * @throws DatabaseException|BaseException
     */
    public function create(): DbalInterface
    {
        $this->getOrmModel()->create();
        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function prop(string $key, $value = null)
    {
        if (func_num_args() === 2) {
            $this->getOrmModel()->$key = $value;
        } else {
            return $this->getOrmModel()->$key ?? null;
        }
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function save(): bool
    {
        return $this->getOrmModel()->save();
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function delete(): bool
    {
        return $this->getOrmModel()->delete();
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function deleteMany(): bool
    {
        return $this->getOrmModel()->delete_many();
    }
}
