<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Modules\Toolkit\Services;

use Quantum\Paginator\Exceptions\PaginatorException;
use Quantum\Paginator\Factories\PaginatorFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Paginator\Enums\PaginatorType;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Paginator\Paginator;
use Quantum\Service\Service;
use ReflectionException;

/**
 * Class LogsService
 * @package Modules\Toolkit
 */
class LogsService extends Service
{
    /**
     * Retrieves a list of available log file names from the logs directory.
     * @throws BaseException
     * @throws ReflectionException
     */
    public function getLogFiles(): array
    {
        $logFiles = fs()->listDirectory(logs_dir());

        $filteredLogFiles = [];

        foreach ($logFiles as $logFile) {
            if (str_contains($logFile, '.log')) {
                $filteredLogFiles[] = fs()->fileNameWithExtension($logFile);
            }
        }

        return $filteredLogFiles;
    }

    /**
     * Parses a specific log file and returns paginated log entries.
     * @throws BaseException
     * @throws PaginatorException
     * @throws ReflectionException
     * @throws ConfigException
     * @throws DiException
     */
    public function getLogEntries(string $logFile, int $perPage, int $currentPage): Paginator
    {
        $logs = fs()->get(logs_dir() . DS . $logFile);

        $parsedLogEntries = [];

        $pattern = '/\[(.*?)] (.*?): (.*?)(?=\n\[\d{4}-\d{2}-\d{2}|\z)/s';

        preg_match_all($pattern, $logs, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $parsedLogEntries[] = [
                'date' => $match[1],
                'level' => $match[2],
                'message' => $match[3]
            ];
        }

        $parsedLogEntries = array_reverse($parsedLogEntries);

        return $this->paginate($parsedLogEntries, $perPage, $currentPage);
    }


    /**
     * Paginates an array of data.
     * @throws BaseException
     * @throws PaginatorException
     */
    private function paginate(array $data, int $perPage, int $currentPage): Paginator
    {
        return PaginatorFactory::create(PaginatorType::ARRAY, [
            "items" => $data,
            "perPage" => $perPage,
            "page" => $currentPage
        ]);
    }
}

