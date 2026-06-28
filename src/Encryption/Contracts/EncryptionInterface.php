<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Encryption\Contracts;

/**
 * Interface EncryptionInterface
 * @package Quantum\Encryption
 */
interface EncryptionInterface
{
    public function encrypt(string $plain): string;

    public function decrypt(string $encrypted): string;
}
