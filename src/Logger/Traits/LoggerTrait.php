<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger\Traits;

use Quantum\Logger\Exceptions\LoggerException;
use Quantum\Storage\FileSystem;

/**
 * Trait LoggerTrait
 * @package Quantum\Logger
 */
trait LoggerTrait
{
    protected FileSystem $fs;

    protected string $logFile;

    /**
     * Initialize the logger
     * @throws LoggerException
     */
    abstract protected function initialize(array $params): void;

    /**
     * Reports a log message
     * @param array<string, mixed>|null $context
     */
    public function report(string $level, string $message, ?array $context = []): void
    {
        $this->fs->append($this->logFile, $this->formatMessage($level, $message, $context));
    }

    /**
     * Formats the log message
     * @param string|array<string, mixed> $message
     * @param array<string, mixed>|null $context
     */
    protected function formatMessage(string $level, $message, ?array $context = []): string
    {
        return sprintf(
            '[%s] %s: %s%s',
            date('Y-m-d H:i:s'),
            ucfirst($level),
            is_array($message) ? json_encode($message, JSON_PRETTY_PRINT) : $message,
            isset($context['trace']) ? (PHP_EOL . $context['trace'] . PHP_EOL) : PHP_EOL
        );
    }
}
