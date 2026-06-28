<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Sleekdb;

use Quantum\Database\Adapters\Sleekdb\Statements\RelatedCriteria;
use Quantum\Database\Adapters\Sleekdb\Statements\Criteria;
use Quantum\Database\Adapters\Sleekdb\Statements\Reducer;
use Quantum\Database\Adapters\Sleekdb\Statements\Result;
use Quantum\Database\Adapters\Sleekdb\Statements\Model;
use Quantum\Database\Adapters\Sleekdb\Statements\Join;
use SleekDB\Exceptions\InvalidConfigurationException;
use Quantum\Database\Exceptions\DatabaseException;
use SleekDB\Exceptions\InvalidArgumentException;
use Quantum\Database\Contracts\DbalInterface;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use SleekDB\Exceptions\IOException;
use SleekDB\QueryBuilder;
use SleekDB\Store;

/**
 * Class SleekDbal
 * @package Quantum\Database
 */
class SleekDbal implements DbalInterface
{
    use Model;
    use Result;
    use Criteria;
    use RelatedCriteria;
    use Reducer;
    use Join;

    protected bool $isNew = false;

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @var array<string, mixed>
     */
    protected $modifiedFields = [];

    /**
     * @var array<int, array<int|string, mixed>|string>
     */
    protected array $criterias = [];

    /**
     * @var array<int, array<int|string, mixed>>
     */
    protected array $havings = [];

    /**
     * @var array<string>
     */
    protected $selected = [];

    /**
     * @var array<string>
     */
    protected $grouped = [];

    /**
     * @var array<string, string>
     */
    protected $ordered = [];

    /**
     * @var int|null
     */
    protected $offset;

    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected $joins = [];

    /**
     * @var array<int, array<int|string, mixed>|string>
     */
    protected array $rootCriterias = [];

    /**
     * @var array<string, array<int, array{0:string,1:string,2:mixed}>>
     */
    protected array $relatedCriteriasByPath = [];

    /**
     * @var array<int, string>
     */
    protected array $requiredRelatedPaths = [];

    protected bool $criteriaPrepared = false;

    /**
     * @var array<int, string>
     */
    protected array $autoSelectedRelatedRoots = [];

    /**
     * Associated model name
     */
    private ?string $modelName;

    /**
     * The database table associated with model
     */
    private string $table;

    /**
     * ID column of table
     */
    private string $idColumn;

    /**
     * Foreign keys (related class name => relation definition)
     * @var array<string, array<string, mixed>>
     */
    private array $foreignKeys;

    /**
     * Hidden fields
     * @var array<string>
     */
    public array $hidden = [];

    private ?Store $ormModel = null;

    private ?QueryBuilder $queryBuilder = null;

    private bool $builderPrepared = false;

    /**
     * Active connection
     * @var array<string, mixed>|null
     */
    private static ?array $connection = null;

    /**
     * Operators map
     * @var string[]
     */
    private array $operators = [
        '=', '!=',
        '>', '>=',
        '<', '<=',
        'IN', 'NOT IN',
        'LIKE', 'NOT LIKE',
        'BETWEEN', 'NOT BETWEEN',
    ];

    /**
     * @param array<string, array<string, mixed>> $foreignKeys
     * @param array<string> $hidden
     */
    public function __construct(
        string $table,
        ?string $modelName = null,
        string $idColumn = 'id',
        array  $foreignKeys = [],
        array  $hidden = []
    ) {
        $this->modelName = $modelName;
        $this->table = $table;
        $this->idColumn = $idColumn;
        $this->foreignKeys = $foreignKeys;
        $this->hidden = $hidden;
    }

    /**
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @inheritDoc
     * @param array<string, mixed> $config
     */
    public static function connect(array $config): void
    {
        self::$connection = $config;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param mixed $modifiedFields
     */
    public function setModifiedFields($modifiedFields): void
    {
        $this->modifiedFields = $modifiedFields;
    }

    public function setIsNew(bool $isNew): void
    {
        $this->isNew = $isNew;
    }

    /**
     * @inheritDoc
     * @return array<string, mixed>|null
     */
    public static function getConnection(): ?array
    {
        return self::$connection;
    }

    /**
     * @inheritDoc
     */
    public static function disconnect(): void
    {
        self::$connection = null;
    }

    /**
     * @inheritDoc
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Gets the ORM model
     * @throws DatabaseException|BaseException|IOException|InvalidArgumentException|InvalidConfigurationException
     */
    public function getOrmModel(): Store
    {
        if (!$this->ormModel) {
            if (!self::getConnection()) {
                throw DatabaseException::missingConfig('database');
            }

            $connection = self::getConnection();

            if (empty($connection['database_dir'])) {
                throw DatabaseException::incorrectConfig();
            }

            $connection['config']['primary_key'] = $this->idColumn;

            $this->ormModel = new Store($this->table, $connection['database_dir'], $connection['config']);
        }

        return $this->ormModel;
    }

    /**
     * @param array<string, mixed>|null $modelData
     */
    public function updateOrmModel(?array $modelData): void
    {
        $this->data = $modelData ?? [];
        $this->modifiedFields = $modelData ?? [];
        $this->isNew = false;
    }

    /**
     * @inheritdoc
     */
    public function truncate(): bool
    {
        try {
            $this->getOrmModel()->deleteStore();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Gets the query builder object
     * @throws ModelException|DatabaseException|BaseException|IOException|InvalidArgumentException|InvalidConfigurationException
     */
    public function getBuilder(): QueryBuilder
    {
        $builder = $this->getQueryBuilder();
        if (!$this->builderPrepared) {
            $this->prepareCriteriaScopesIfNeeded();
            $this->applyBuilderModifiers($builder);
            $this->builderPrepared = true;
        }
        return $builder;
    }

    /**
     * Gets foreign keys
     * @return array<string, array<string, mixed>>
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * Gets the associated model name
     */
    public function getModelName(): string
    {
        return $this->modelName ?? '';
    }

    /**
     * Resets the builder state
     */
    protected function resetBuilderState(): void
    {
        $this->criterias = [];
        $this->rootCriterias = [];
        $this->relatedCriteriasByPath = [];
        $this->requiredRelatedPaths = [];
        $this->criteriaPrepared = false;
        $this->autoSelectedRelatedRoots = [];
        $this->havings = [];
        $this->selected = [];
        $this->grouped = [];
        $this->ordered = [];
        $this->offset = null;
        $this->limit = null;
        $this->joins = [];
        $this->queryBuilder = null;
        $this->builderPrepared = false;
    }

    /**
     * Builds a dot-notated join path from current path and next relation table.
     */
    protected function buildJoinPath(string $currentPath, string $table): string
    {
        return $currentPath !== '' ? $currentPath . '.' . $table : $table;
    }

    /**
     * Ensures a reusable query builder instance exists for current adapter state.
     * @return QueryBuilder
     * @throws BaseException
     * @throws DatabaseException
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws InvalidConfigurationException
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = $this->getOrmModel()->createQueryBuilder();
        }

        return $this->queryBuilder;
    }

    /**
     * Prepares root/related criteria scopes once per builder lifecycle.
     */
    protected function prepareCriteriaScopesIfNeeded(): void
    {
        if (!$this->criteriaPrepared) {
            $this->prepareCriteriaScopes();
        }
    }

    /**
     * Applies all collected query modifiers on the given builder.
     * @throws ModelException|InvalidArgumentException
     */
    protected function applyBuilderModifiers(QueryBuilder $builder): void
    {
        $this->applySelectModifier($builder);
        $this->applyJoinModifier();
        $this->applyWhereModifier($builder);
        $this->applyHavingModifier($builder);
        $this->applyGroupModifier($builder);
        $this->applyOrderModifier($builder);
        $this->applyPaginationModifier($builder);
    }

    protected function applySelectModifier(QueryBuilder $builder): void
    {
        if ($this->selected !== []) {
            $builder->select($this->buildSelectForQuery());
        }
    }

    /**
     * @throws ModelException
     */
    protected function applyJoinModifier(): void
    {
        if ($this->joins !== []) {
            $this->applyJoins();
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function applyWhereModifier(QueryBuilder $builder): void
    {
        if ($this->rootCriterias !== []) {
            $builder->where($this->rootCriterias);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function applyHavingModifier(QueryBuilder $builder): void
    {
        if ($this->havings !== []) {
            $builder->having($this->havings);
        }
    }

    protected function applyGroupModifier(QueryBuilder $builder): void
    {
        if ($this->grouped !== []) {
            $builder->groupBy($this->grouped);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function applyOrderModifier(QueryBuilder $builder): void
    {
        if ($this->ordered !== []) {
            $builder->orderBy($this->ordered);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function applyPaginationModifier(QueryBuilder $builder): void
    {
        if ($this->offset) {
            $builder->skip($this->offset);
        }

        if ($this->limit) {
            $builder->limit($this->limit);
        }
    }

}
