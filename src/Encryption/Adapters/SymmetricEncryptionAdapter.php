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
use Quantum\App\Exceptions\AppException;
use ReflectionException;

/**
 * Class SymmetricEncryptionAdapter
 * @package Quantum\Encryption
 */
class SymmetricEncryptionAdapter implements EncryptionInterface
{
    /**
     * Cipher method
     */
    public const CIPHER_METHOD = 'aes-256-cbc';

    private string $appKey;

    /**
     * @throws BaseException|ReflectionException
     */
    public function __construct()
    {
        $appKey = config()->get('app.key');

        if (!$appKey) {
            throw AppException::missingAppKey();
        }

        $this->appKey = $appKey;
    }

    /**
     * Encrypts the string
     * @throws CryptorException
     */
    public function encrypt(string $plain): string
    {
        $iv = $this->generateIV();

        $encrypted = openssl_encrypt($plain, self::CIPHER_METHOD, $this->appKey, 0, $iv);

        if ($encrypted === false) {
            throw CryptorException::invalidCipher();
        }

        return base64_encode(base64_encode($encrypted) . '::' . base64_encode($iv));
    }

    /**
     * Decrypts the string
     * @throws CryptorException
     */
    public function decrypt(string $encrypted): string
    {
        if (!valid_base64($encrypted)) {
            return $encrypted;
        }

        $data = explode('::', base64_decode($encrypted), 2);

        if (count($data) < 2) {
            throw CryptorException::invalidCipher();
        }

        $encryptedData = base64_decode($data[0]);
        $iv = base64_decode($data[1]);

        $decrypted = openssl_decrypt($encryptedData, self::CIPHER_METHOD, $this->appKey, 0, $iv);

        if ($decrypted === false) {
            throw CryptorException::invalidCipher();
        }

        return $decrypted;
    }

    /**
     * Generates initialization vector
     */
    private function generateIV(): string
    {
        $length = openssl_cipher_iv_length(self::CIPHER_METHOD);

        $bytes = openssl_random_pseudo_bytes($length);

        return $bytes;
    }
}
