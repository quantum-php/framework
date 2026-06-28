<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Hasher;

use Quantum\Hasher\Exceptions\HasherException;

/**
 * Hasher class
 * @package Quantum\Hasher
 */
class Hasher
{
    /**
     * Default algorithm for hashing
     */
    private const DEFAULT_ALGORITHM = PASSWORD_BCRYPT;

    /**
     * Default cost for hashing
     */
    private const DEFAULT_COST = 12;

    /**
     * The algorithm
     */
    private string $algorithm = self::DEFAULT_ALGORITHM;

    /**
     * The cost
     */
    private int $cost = self::DEFAULT_COST;

    /**
     * Sets the algorithm
     */
    public function setAlgorithm(string $algorithm): Hasher
    {
        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * Gets the current algorithm
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * Sets the cost
     * @throws HasherException
     */
    public function setCost(int $cost): Hasher
    {
        if ($this->algorithm === PASSWORD_BCRYPT && ($cost < 4 || $cost > 31)) {
            throw HasherException::invalidBcryptCost();
        }

        $this->cost = $cost;
        return $this;
    }

    /**
     * Gets the current cost
     */
    public function getCost(): int
    {
        return $this->cost;
    }

    /**
     * Hashes the given string
     */
    public function hash(string $password): ?string
    {
        return password_hash($password, $this->algorithm, ['cost' => $this->cost]);
    }

    /**
     * Checks if re-hash needed
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, ['cost' => $this->cost]);
    }

    /**
     * Checks the given string against the hash
     */
    public function check(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Gets an info of given hash
     * @return array<string, mixed>|null
     */
    public function info(string $hash): ?array
    {
        return password_get_info($hash);
    }
}
