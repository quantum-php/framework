<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Idiorm;

use Quantum\Database\Adapters\Idiorm\Statements\Transaction;
use Quantum\Database\Adapters\Idiorm\Statements\Criteria;
use Quantum\Database\Adapters\Idiorm\Statements\Reducer;
use Quantum\Database\Adapters\Idiorm\Statements\Result;
use Quantum\Database\Adapters\Idiorm\Statements\Query;
use Quantum\Database\Adapters\Idiorm\Statements\Model;
use Quantum\Database\Adapters\Idiorm\Statements\Join;
use Quantum\Database\Contracts\RelationalInterface;
use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Database\Contracts\DbalInterface;
use Quantum\App\Exceptions\BaseException;
use InvalidArgumentException;
use ORM;
use PDO;
use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Class IdiormDbal
 * @package Quantum\Database
 */
class IdiormDbal implements DbalInterface, RelationalInterface
{
    use Transaction;
    use Model;
    use Result;
    use Criteria;
    use Reducer;
    use Join;
    use Query;

    /**
     * SQLite driver
     */
    public const DRIVER_SQLITE = 'sqlite';

    /**
     * MySQL driver
     */
    public const DRIVER_MYSQL = 'mysql';

    /**
     * PostgresSQL driver
     */
    public const DRIVER_PGSQL = 'pgsql';

    /**
     * Default charset
     */
    public const DEFAULT_CHARSET = 'utf8';

    /**
     * Associated model name
     * @var string|null
     */
    private $modelName;

    /**
     * The database table associated with model
     * @var string
     */
    private $table;

    /**
     * Id column of table
     * @var string
     */
    private $idColumn;

    /**
     * Foreign keys
     * @var array<string, array<string, mixed>>
     */
    private $foreignKeys;

    /**
     * Hidden fields
     * @var array<string>
     */
    private array $hidden;

    /**
     * Idiorm Patch object
     */
    private ?IdiormPatch $ormPatch = null;

    /**
     * ORM Model
     */
    private ?ORM $ormModel = null;

    /**
     * Active connection
     * @var array<string, mixed>|null
     */
    private static ?array $connection = null;

    /**
     * Operators map
     * @var array<string, string|null>
     */
    private array $operators = [
        '=' => 'where_equal',
        '!=' => 'where_not_equal',
        '>' => 'where_gt',
        '>=' => 'where_gte',
        '<' => 'where_lt',
        '<=' => 'where_lte',
        'IN' => 'where_in',
        'NOT IN' => 'where_not_in',
        'LIKE' => 'where_like',
        'NOT LIKE' => 'where_not_like',
        'NULL' => 'where_null',
        'NOT NULL' => 'where_not_null',
        '#=#' => null,
    ];

    /**
     * ORM Class
     */
    private static string $ormClass = ORM::class;

    /**
     * @param array<string, array<string, mixed>> $foreignKeys
     * @param array<string> $hidden
     */
    public function __construct(
        string $table,
        ?string $modelName = null,
        string $idColumn = 'id',
        array $foreignKeys = [],
        array $hidden = []
    ) {
        $this->modelName = $modelName;
        $this->table = $table;
        $this->idColumn = $idColumn;
        $this->foreignKeys = $foreignKeys;
        $this->hidden = $hidden;
    }

    /**
     * @inheritDoc
     * @param array<string, mixed> $config
     */
    public static function connect(array $config): void
    {
        $driver = $config['driver'] ?? '';
        $charset = $config['charset'] ?? self::DEFAULT_CHARSET;

        $configuration = self::getBaseConfig($driver, $config) + self::getDriverConfig($driver, $config, $charset);

        (self::$ormClass)::configure($configuration);

        self::$connection = (self::$ormClass)::get_config();
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
        (self::$ormClass)::reset_db();
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
     * @throws BaseException
     */
    public function getOrmModel(): ORM
    {
        $model = $this->ormModel;

        if (!$model) {
            if (!self::getConnection()) {
                throw DatabaseException::missingConfig('database');
            }

            $model = (self::$ormClass)::for_table($this->table)->use_id_column($this->idColumn);
            $this->ormModel = $model;
        }

        return $model;
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
     * @inheritDoc
     */
    public function truncate(): bool
    {
        try {
            $this->getOrmModel()->raw_execute("DELETE FROM {$this->table}");
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @return void
     */
    protected function updateOrmModel(ORM $ormModel)
    {
        $this->ormModel = $ormModel;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     * @throws DiException|ReflectionException
     */
    protected static function getBaseConfig(string $driver, array $config): array
    {
        return [
            'connection_string' => self::buildConnectionString($driver, $config),
            'logging' => config()->get('app.debug', false),
            'error_mode' => PDO::ERRMODE_EXCEPTION,
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected static function getDriverConfig(string $driver, array $config, string $charset): array
    {
        if ($driver === self::DRIVER_MYSQL || $driver === self::DRIVER_PGSQL) {
            return [
                'username' => $config['username'] ?? null,
                'password' => $config['password'] ?? null,
                'driver_options' => [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $charset],
            ];
        }

        if ($driver === self::DRIVER_SQLITE) {
            return [];
        }

        throw new InvalidArgumentException("Unsupported driver: $driver");
    }

    /**
     * Builds connection string
     * @param array<string, mixed> $config
     */
    protected static function buildConnectionString(string $driver, array $config): string
    {
        if ($driver === self::DRIVER_SQLITE) {
            return $driver . ':' . ($config['database'] ?? '');
        }

        if ($driver === self::DRIVER_MYSQL || $driver === self::DRIVER_PGSQL) {
            $parts = [
                'host=' . ($config['host'] ?? ''),
                'dbname=' . ($config['dbname'] ?? ''),
            ];

            if (!empty($config['port'])) {
                $parts[] = 'port=' . $config['port'];
            }

            if (!empty($config['charset'])) {
                $parts[] = 'charset=' . $config['charset'];
            }

            return $driver . ':' . implode(';', $parts);
        }

        throw new InvalidArgumentException("Unsupported driver: $driver");
    }
}
