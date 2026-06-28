<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator\Adapters;

use Quantum\Paginator\Contracts\PaginatorInterface;
use Quantum\Paginator\Traits\PaginatorTrait;
use Quantum\App\Exceptions\BaseException;
use Quantum\Model\Exceptions\ModelException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Model\ModelCollection;
use Quantum\Model\DbModel;
use Quantum\Model\Model;
use ReflectionException;

/**
 * Class ModelPaginator
 * @package Quantum\Paginator
 */
class ModelPaginator implements PaginatorInterface
{
    use PaginatorTrait;

    private string $modelClass;

    private DbModel $model;

    /**
     * @throws DiException|ReflectionException|ModelException|BaseException
     */
    public function __construct(DbModel $model, int $perPage, int $page = 1)
    {
        $this->initialize($perPage, $page);

        $this->model = $model;
        $this->modelClass = $model->getModelName();
        $this->total = $this->getCountFromClonedModel($model);
    }

    /**
     * @inheritDoc
     * @return ModelCollection<int|string, Model>
     * @throws BaseException
     */
    public function data(): ModelCollection
    {
        $result = $this->model
            ->limit($this->perPage)
            ->offset($this->perPage * ($this->page - 1))
            ->get();

        if ($this->modelClass != '@anonymous') {
            $result = array_map(fn ($item): ?DbModel => wrapToModel($item->getOrmInstance(), $this->modelClass), iterator_to_array($result));
        }

        /** @var ModelCollection<int|string, Model> $collection */
        $collection = new ModelCollection($result);
        return $collection;
    }

    /**
     * @inheritDoc
     * @throws BaseException
     */
    public function firstItem(): ?Model
    {
        $data = $this->data();

        if ($data->isEmpty()) {
            return null;
        }

        return $data->first();
    }

    /**
     * @inheritDoc
     * @throws BaseException
     */
    public function lastItem(): ?Model
    {
        $data = $this->data();

        if ($data->isEmpty()) {
            return null;
        }

        return $data->last();
    }

    /**
     * Counts results on an isolated model/ORM clone so total calculation cannot mutate
     * the live model query state later used by paginator data().
     * @throws ModelException|BaseException
     */
    private function getCountFromClonedModel(DbModel $model): int
    {
        $countModel = clone $model;
        $countModel->setOrmInstance(clone $model->getOrmInstance());

        return $countModel->count();
    }
}
