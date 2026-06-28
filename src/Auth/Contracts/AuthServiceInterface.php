<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth\Contracts;

use Quantum\Auth\User;

/**
 * Interface AuthServiceInterface
 * @package Quantum\Auth
 */
interface AuthServiceInterface
{
    /**
     * Get
     */
    public function get(string $field, ?string $value): ?User;

    /**
     * Add
     * @param array<string, mixed> $data
     */
    public function add(array $data): User;

    /**
     * Update
     * @param array<string, mixed> $data
     */
    public function update(string $field, ?string $value, array $data): ?User;

    /**
     * User Schema
     * @return array<string, mixed>
     */
    public function userSchema(): array;
}
