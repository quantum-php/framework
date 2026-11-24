<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger\Traits;

use Quantum\Logger\Exceptions\LoggerException;
use Quantum\Config\ConfigSection;
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
    abstract protected function initialize(ConfigSection $config): void;

    /**
     * Reports a log message
     * @param array<string, mixed>|null $context
     */
    public function report(string $level, string|Stringable $message, ?array $context = []): void
    {
        $this->fs->append($this->logFile, $this->formatMessage($level, $message, $context));
    }

    /**
     * Formats the log message: the context follows the message as JSON, and a `trace`
     * that is text follows on its own lines; a `trace` that is not text stays in the JSON
     * @param array<string, mixed>|null $context
     */
    protected function formatMessage(string $level, string|Stringable $message, ?array $context = []): string
    {
        $context ??= [];
        $trace = $context['trace'] ?? null;
        $traceIsText = is_string($trace) || $trace instanceof Stringable;

        if ($trace === null || $traceIsText) {
            unset($context['trace']);
        }

        return sprintf(
            '[%s] %s: %s%s%s',
            date('Y-m-d H:i:s'),
            ucfirst($level),
            (string) $message,
            $context !== [] ? ' ' . $this->encodeContext($context) : '',
            $traceIsText ? PHP_EOL . $trace . PHP_EOL : PHP_EOL
        );
    }

    /**
     * Encodes the context as one line of JSON: an exception becomes its class, message, file and line,
     * a Stringable value its text, and a value JSON cannot encode becomes null; when a value throws
     * while it is converted, a marker replaces the context, so logging never fails because of it
     * @param array<string, mixed> $context
     */
    private function encodeContext(array $context): string
    {
        try {
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
        } catch (Throwable) {
            return '{"context":"not encodable"}';
        }
    }
}
