<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Contracts;

/**
 * Interface TokenServiceInterface
 * @package Quantum\Storage
 */
interface TokenServiceInterface
{
    public function getAccessToken(): string;

    public function getRefreshToken(): string;

    public function saveTokens(string $accessToken, ?string $refreshToken = null): bool;
}
