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
 * Interface AuthenticatableInterface
 * @package Quantum\Auth
 */
interface AuthenticatableInterface
{
    /**
     * Auth user key
     */
    public const AUTH_USER = 'auth_user';

    /**
     * Sign In
     * @return mixed
     */
    public function signin(string $username, string $password);

    /**
     * Sign Out
     */
    public function signout(): bool;

    /**
     * Check
     */
    public function check(): bool;

    /**
     * User
     */
    public function user(): ?User;
}
