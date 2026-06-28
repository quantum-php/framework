<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Modules\Toolkit\Services;

use Quantum\Storage\Exceptions\FileSystemException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Paginator\Paginator;
use Quantum\Service\Service;
use ReflectionException;

/**
 * Class DatabaseService
 * @package Modules\Toolkit
 */
class DatabaseService extends Service
{
    protected string $storeDirectory;

    public function __construct()
    {
        $this->storeDirectory = base_dir() . DS . 'shared' . DS . 'store';
    }

    /**
     * Get tables
     * @throws BaseException
     * @throws ReflectionException
     */
    public function getTables(): array
    {
        $databaseFolder = fs()->listDirectory($this->storeDirectory);

        $tables = [];

        foreach ($databaseFolder as $item) {
            try {
                if (fs()->isDirectory($item)) {
                    $tables[] = fs()->fileName($item);
                }
            } catch (FileSystemException $e) {
                continue;
            }
        }

        return $tables;
    }

    /**
     * Check table existence
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function tableExists(string $tableName, ?string $except = null): bool
    {
        if ($except !== null && $tableName === $except) {
            return false;
        }

        return fs()->isDirectory($this->storeDirectory . DS . $tableName);
    }

    /**
     * Get table data
     */
    public function getTableData(string $tableName, int $perPage, int $currentPage): array
    {
        $paginator = dynamicModel($tableName)
            ->orderBy("id", "DESC")
            ->paginate($perPage, $currentPage);

        return [
            'columns' => $this->extractColumns($paginator),
            'data' => $paginator->data(),
            'pagination' => $paginator
        ];
    }

    /**
     * Create table row
     */
    public function createTableRow(string $tableName, array $data): void
    {
        $model = dynamicModel($tableName);

        $row = $model->create();

        foreach ($data as $field => $value) {
            $row->prop($field, $value);
        }

        $row->save();
    }

    /**
     * Update table row
     */
    public function updateTable(string $tableName, int $id, array $data): void
    {
        $model = dynamicModel($tableName);

        $row = $model->findOne($id);

        foreach ($data as $field => $value) {
            $row->prop($field, $value);
        }

        $row->save();
    }

    /**
     * Delete table row
     */
    public function deleteTableRow(string $tableName, int $id): void
    {
        dynamicModel($tableName)->findOne($id)->delete();
    }

    /**
     * Extract columns
     */
    private function extractColumns(Paginator $paginator): array
    {
        $firstRow = $paginator->firstItem();

        if (!$firstRow) {
            return [];
        }

        $columns = array_keys($firstRow->asArray());

        usort($columns, function ($a, $b) {
            if ($a === 'id') {
                return -1;
            }
            if ($b === 'id') {
                return 1;
            }
            return 0;
        });

        return $columns;
    }
}

