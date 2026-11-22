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
use Stringable;
use Throwable;

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
    public function report(string $level, string|Stringable $message, ?array $context = []): void
    {
        $this->fs->append($this->logFile, $this->formatMessage($level, $message, $context));
    }

    /**
     * Formats the log message: the context, without `trace`, follows the message as JSON,
     * and `trace` follows on its own lines
     * @param array<string, mixed>|null $context
     */
    protected function formatMessage(string $level, string|Stringable $message, ?array $context = []): string
    {
        $context ??= [];
        $trace = isset($context['trace']) ? (PHP_EOL . $context['trace'] . PHP_EOL) : PHP_EOL;
        unset($context['trace']);

        return sprintf(
            '[%s] %s: %s%s%s',
            date('Y-m-d H:i:s'),
            ucfirst($level),
            (string) $message,
            $context !== [] ? ' ' . $this->encodeContext($context) : '',
            $trace
        );
    }

    /**
     * Encodes the context as one line of JSON: an exception becomes its class, message, file and line,
     * a Stringable value its text, and a value JSON cannot encode becomes null
     * @param array<string, mixed> $context
     */
    private function encodeContext(array $context): string
    {
        $values = array_map(static fn (mixed $value): mixed => match (true) {
            $value instanceof Throwable => [
                'class' => $value::class,
                'message' => $value->getMessage(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
            ],
            $value instanceof Stringable => (string) $value,
            default => $value,
        }, $context);

        return json_encode(
            $values,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
        ) ?: '';
    }
}
