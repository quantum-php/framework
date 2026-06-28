<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger;

use Quantum\Logger\Contracts\ReportableInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Class Logger
 * @package Quantum\Logger
 */
class Logger implements LoggerInterface
{
    private ReportableInterface $adapter;

    public function __construct(ReportableInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): ReportableInterface
    {
        return $this->adapter;
    }

    /**
     * @inheritDoc
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = []): void
    {
        if (is_debug_mode() || LoggerConfig::getLogLevel($level) >= LoggerConfig::getAppLogLevel()) {
            $this->adapter->report($level, $message, $context);
        }
    }
}
