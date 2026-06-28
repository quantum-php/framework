<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Encryption\Factories\CryptorFactory;
use Quantum\Encryption\Enums\CryptorType;
use Quantum\App\Exceptions\BaseException;

/**
 * Encodes the data cryptographically
 * @param mixed $data
 * @throws BaseException|ReflectionException
 */
function crypto_encode($data, string $type = CryptorType::SYMMETRIC): string
{
    $serializedData = serialize($data);

    return CryptorFactory::get($type)->encrypt($serializedData);
}

/**
 * @return mixed|string
 * @throws BaseException|ReflectionException
 */
function crypto_decode(string $encryptedData, string $type = CryptorType::SYMMETRIC)
{
    $cryptor = CryptorFactory::get($type);

    $decryptedData = $cryptor->decrypt($encryptedData);

    $unSerializedData = @unserialize($decryptedData);

    if ($unSerializedData !== false) {
        return $unSerializedData;
    }

    return $decryptedData;
}
