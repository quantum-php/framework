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
use Quantum\Config\ConfigSection;
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
     * @throws LoggerException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function __construct(ConfigSection $config)
    {
        $this->fs = FileSystemFactory::get();
        $this->initialize($config);
    }

    /**
     * Initialize the adapter for Single logs
     * @throws LoggerException
     */
    protected function initialize(ConfigSection $config): void
    {
        if (!$this->fs->extension($config->get('path'))) {
            throw LoggerException::logPathIsNotFile($config->get('path'));
        }

        $this->logFile = $config->get('path');
    }
}
