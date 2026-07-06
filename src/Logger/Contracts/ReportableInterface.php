<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger\Contracts;

use Stringable;

/**
 * Interface ReportableInterface
 * @package Quantum\Logger
 */
interface ReportableInterface
{
    /**
     * Reports a message
     * @param array<string, mixed>|null $context
     */
    public function report(string $level, string|Stringable $message, ?array $context = []): void;

}
