<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Jwt;

use Quantum\Environment\Exceptions\EnvException;
use Quantum\Jwt\Exceptions\JwtException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Class JwtToken
 * @package Quantum\Jwt
 * @uses JWT
 */
class JwtToken extends JWT
{
    /**
     * JWT secret key
     */
    private string $key;

    /**
     * Encryption algorithm
     */
    private string $algorithm = 'HS256';

    /**
     * Payload data
     * @var array<string, mixed>
     */
    private array $payload = [];

    private ?object $fetchedPayload = null;

    /**
     * JwtToken constructor.
     * @throws EnvException
     */
    public function __construct(?string $key = null)
    {
        $this->key = $key ?? env('APP_KEY');
    }

    /**
     * Sets extra leeway time
     */
    public function setLeeway(int $leeway): JwtToken
    {
        parent::$leeway = $leeway;
        return $this;
    }

    /**
     * Sets the encryption algorithm
     */
    public function setAlgorithm(string $algorithm): JwtToken
    {
        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * Sets the claim
     * @param mixed $value
     */
    public function setClaim(string $key, $value): JwtToken
    {
        $this->payload[$key] = $value;
        return $this;
    }

    /**
     * Set claims
     * @param array<string, mixed> $claims
     */
    public function setClaims(array $claims): JwtToken
    {
        foreach ($claims as $key => $value) {
            $this->payload[$key] = $value;
        }

        return $this;
    }

    /**
     * Sets user data
     * @param array<string, mixed> $data
     */
    public function setData(array $data): JwtToken
    {
        $this->payload['data'] = $data;
        return $this;
    }

    /**
     * Composes and signs the JWT
     * @param array<string, mixed>|null $head
     * @throws JwtException
     */
    public function compose(?string $keyId = null, ?array $head = null): string
    {
        if (empty($this->payload)) {
            throw JwtException::payloadNotFound();
        }

        return parent::encode($this->payload, $this->key, $this->algorithm, $keyId, $head);
    }

    /**
     * Retrieve and verifies the JWT
     */
    public function retrieve(string $jwt): JwtToken
    {
        $this->fetchedPayload = parent::decode($jwt, new Key($this->key, $this->algorithm));
        return $this;
    }

    /**
     * Fetches the payload
     * @return object
     */
    public function fetchPayload(): ?object
    {
        return $this->fetchedPayload;
    }

    /**
     * Fetches the user data
     * @return array<string, mixed>|null
     */
    public function fetchData(): ?array
    {
        return isset($this->fetchedPayload->data) ? (array) $this->fetchedPayload->data : null;
    }

    /**
     * Fetches the claim
     * @return mixed|null
     */
    public function fetchClaim(string $key)
    {
        return $this->fetchedPayload->$key ?? null;
    }
}
