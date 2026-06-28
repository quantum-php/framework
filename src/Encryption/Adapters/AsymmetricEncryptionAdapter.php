<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Encryption\Adapters;

use Quantum\Encryption\Contracts\EncryptionInterface;
use Quantum\Encryption\Exceptions\CryptorException;
use Quantum\App\Exceptions\BaseException;

/**
 * Class AsymmetricEncryptionAdapter
 * @package Quantum\Encryption
 */
class AsymmetricEncryptionAdapter implements EncryptionInterface
{
    /**
     * Digest algorithm
     */
    public const DIGEST_ALGO = 'SHA512';

    /**
     * Key bits
     */
    public const KEY_BITS = 1024;

    private ?string $publicKey = null;

    private ?string $privateKey = null;

    /**
     * @throws BaseException
     */
    public function __construct()
    {
        $keyPair = $this->generateKeyPair();

        $this->publicKey = $keyPair['public'];
        $this->privateKey = $keyPair['private'];
    }

    /**
     * Encrypts the string
     * @throws CryptorException
     */
    public function encrypt(string $plain): string
    {
        if (!$this->publicKey) {
            throw CryptorException::publicKeyNotProvided();
        }

        openssl_public_encrypt($plain, $encrypted, $this->publicKey);
        return base64_encode($encrypted);
    }

    /**
     * Decrypts the string
     * @throws CryptorException
     */
    public function decrypt(string $encrypted): string
    {
        if (!$this->privateKey) {
            throw CryptorException::privateKeyNotProvided();
        }

        openssl_private_decrypt(base64_decode($encrypted), $decrypted, $this->privateKey);
        return $decrypted;
    }

    /**
     * Generate key pairs
     * @return array<string, string>
     * @throws BaseException
     */
    private function generateKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => self::KEY_BITS,
            'digest_alg' => self::DIGEST_ALGO,
        ]);

        if (!$resource) {
            throw CryptorException::missingConfig('openssl.cnf');
        }

        openssl_pkey_export($resource, $privateKey);

        $details = openssl_pkey_get_details($resource);

        if ($details === false) {
            throw CryptorException::missingConfig('openssl.cnf');
        }

        $publicKey = $details['key'];

        return [
            'private' => $privateKey,
            'public' => $publicKey,
        ];
    }
}
