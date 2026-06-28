<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Service\Exceptions\ServiceException;
use Quantum\Service\Factories\ServiceFactory;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Service\Service;

/**
 * Gets or creates service instance
 * @param class-string<T> $serviceClass
 * @return T
 * @throws BaseException
 * @throws DiException
 * @throws ReflectionException
 * @throws ServiceException
 * @template T of Service
 */
function service(string $serviceClass, bool $singleton = false): Service
{
    return $singleton ? ServiceFactory::get($serviceClass) : ServiceFactory::create($serviceClass);
}
