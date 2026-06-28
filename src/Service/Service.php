<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Service;

use Quantum\Service\Exceptions\ServiceException;
use Quantum\App\Exceptions\BaseException;

/**
 * Class Service
 * @package Quantum\Service
 */
abstract class Service
{
    /**
     * Handles the missing methods of the service
     * @param array<mixed> $arguments
     * @return never
     * @throws BaseException
     */
    public function __call(string $method, array $arguments)
    {
        throw ServiceException::methodNotSupported($method, Service::class);
    }
}
