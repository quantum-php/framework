<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Model;

use Quantum\Paginator\Exceptions\PaginatorException;
use Quantum\Paginator\Factories\PaginatorFactory;
use Quantum\Database\Contracts\DbalInterface;
use Quantum\Model\Exceptions\ModelException;
use Quantum\Paginator\Enums\PaginatorType;
use Quantum\App\Exceptions\BaseException;
use Quantum\Paginator\Paginator;

/**
 * Class DbModel
 * @package Quantum\Model
 *
 * @method string getTable()
 * @method string getModelName()
 * @method self select(...$columns)
 * @method self criteria(string $column, string $operator, $value = null)
 * @method self criterias(...$criterias)
 * @method self having(string $column, string $operator, string $value = null)
 * @method self groupBy(string $column)
 * @method self orderBy(string $column, string $direction)
 * @method self offset(int $offset)
 * @method self limit(int $limit)
 * @method int count()
 * @method array<mixed> asArray()
 * @method bool truncate()
 * @method bool deleteMany()
 * @method self joinTo(DbModel $model, bool $switch = true)
 * @method self isNull(string $column)
 * @method self isNotNull(string $column)
 */
abstract class DbModel extends Model
{
    /**
     * Database table
     */
    public string $table = '';

    /**
     * Primary key column
     */
    public string $idColumn = 'id';

    /**
     * ORM instance (Idiorm or SleekDB)
     */
    protected ?DbalInterface $ormInstance = null;

    /**
     * Set ORM instance
     */
    public function setOrmInstance(DbalInterface $ormInstance): void
    {
        $this->ormInstance = $ormInstance;
    }

    /**
     * Get ORM instance
     * @throws ModelException
     */
    public function getOrmInstance(): DbalInterface
    {
        if (!isset($this->ormInstance)) {
            throw ModelException::ormIsNotSet();
        }

        return $this->ormInstance;
    }

    /**
     * @return array<string, mixed>
     */
    public function relations(): array
    {
        return [];
    }

    /**
     * Finds the record by primary key and returns a new model instance
     * @throws BaseException
     */
    public function findOne(int $id): ?DbModel
    {
        $orm = $this->getOrmInstance()->findOne($id);

        return wrapToModel($orm, static::class);
    }

    /**
     * Finds the record by given column and value and returns a new model instance
     * @param mixed $value
     * @throws BaseException
     */
    public function findOneBy(string $column, $value): ?DbModel
    {
        $orm = $this->getOrmInstance()->findOneBy($column, $value);

        return wrapToModel($orm, static::class);
    }

    /**
     * Gets the first record and returns a new model instance
     * @throws BaseException
     */
    public function first(): ?DbModel
    {
        $orm = $this->getOrmInstance()->first();

        return wrapToModel($orm, static::class);
    }

    /**
     * Fetch multiple results
     * @return ModelCollection<int|string, Model>
     * @throws BaseException
     */
    public function get(): ModelCollection
    {
        $models = [];

        foreach ($this->getOrmInstance()->get() as $item) {
            $model = wrapToModel($item, static::class);
            if ($model !== null) {
                $models[] = $model;
            }
        }

        /** @var ModelCollection<int|string, Model> $collection */
        $collection = new ModelCollection($models);
        return $collection;
    }

    /**
     * Paginates the result
     * @throws BaseException
     * @throws PaginatorException
     */
    public function paginate(int $perPage, int $currentPage = 1): Paginator
    {
        return PaginatorFactory::create(PaginatorType::MODEL, [
            'model' => $this,
            'perPage' => $perPage,
            'page' => $currentPage,
        ]);
    }

    /**
     * Creates a new record
     * @return $this
     * @throws ModelException
     */
    public function create(): self
    {
        $this->attributes = [];
        $this->getOrmInstance()->create();
        return $this;
    }

    /**
     * Save model
     * @throws ModelException
     */
    public function save(): bool
    {
        if (method_exists($this, 'touchTimestamps')) {
            $this->touchTimestamps();
        }

        $this->syncAttributesToOrm();

        $result = $this->getOrmInstance()->save();

        $this->syncPrimaryKeyFromOrm();

        return $result;
    }

    /**
     * Delete model
     * @throws ModelException
     */
    public function delete(): bool
    {
        return $this->getOrmInstance()->delete();
    }

    /**
     * @param array<string, mixed> $data
     * @return $this
     */
    public function hydrateFromOrm(array $data): self
    {
        $this->attributes = $data;
        return $this;
    }

    /**
     * @param array<mixed> $args
     * @return mixed
     * @throws ModelException|BaseException
     */
    public function __call(string $method, array $args = [])
    {
        $orm = $this->getOrmInstance();

        if (!method_exists($orm, $method)) {
            throw ModelException::methodNotSupported(
                $method,
                $orm::class
            );
        }

        $result = $orm->{$method}(...$args);

        return $result instanceof DbalInterface ? $this : $result;
    }

    /**
     * @return string[]
     */
    public function __sleep()
    {
        return [
            'table',
            'idColumn',
            'hidden',
            'attributes',
        ];
    }

    /**
     * Sync model attributes into ORM
     * @throws ModelException
     */
    protected function syncAttributesToOrm(): void
    {
        $orm = $this->getOrmInstance();

        foreach ($this->attributes as $key => $value) {
            if ($key === $this->idColumn) {
                continue;
            }

            $orm->prop($key, $value);
        }
    }

    protected function shouldFill(string $key): bool
    {
        if ($key === $this->idColumn) {
            return false;
        }

        return parent::shouldFill($key);
    }

    /**
     * Syncs primary key from ORM to model attributes
     * @throws ModelException
     */
    private function syncPrimaryKeyFromOrm(): void
    {
        $id = $this->getOrmInstance()->prop($this->idColumn);

        if ($id !== null) {
            $this->attributes[$this->idColumn] = $id;
        }
    }
}
