<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger\Adapters;

use Quantum\Logger\Contracts\ReportableInterface;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Logger\Exceptions\LoggerException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Logger\Traits\LoggerTrait;
use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Class SingleAdapter
 * @package Quantum\Logger
 */
class SingleAdapter implements ReportableInterface
{
    use LoggerTrait;

    /**
     * SingleAdapter constructor
     * @param array<string, mixed> $params
     * @throws LoggerException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function __construct(array $params)
    {
        $this->fs = FileSystemFactory::get();
        $this->initialize($params);
    }

    /**
     * Initialize the adapter for Single logs
     * @param array<string, mixed> $params
     * @throws LoggerException
     */
    protected function initialize(array $params): void
    {
        if (!$this->fs->extension($params['path'])) {
            throw LoggerException::logPathIsNotFile($params['path']);
        }

        $this->logFile = $params['path'];
    }
}
