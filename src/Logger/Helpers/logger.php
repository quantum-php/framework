<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Logger\Factories\LoggerFactory;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Logger\Logger;

/**
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function logger(?string $adapter = null): Logger
{
    return LoggerFactory::get($adapter);
}

/**
 * Reports error
 * @param array<string, mixed> $context
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function error(string $var, array $context = []): void
{
    LoggerFactory::get()->error($var, $context);
}

/**
 * Reports warning
 * @param array<string, mixed> $context
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function warning(string $var, array $context = []): void
{
    LoggerFactory::get()->warning($var, $context);
}

/**
 * Reports notice
 * @param array<string, mixed> $context
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function notice(string $var, array $context = []): void
{
    LoggerFactory::get()->notice($var, $context);
}

/**
 * Reports info
 * @param array<string, mixed> $context
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function info(string $var, array $context = []): void
{
    LoggerFactory::get()->info($var, $context);
}

/**
 * Reports debug
 * @param array<string, mixed> $context
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function debug(string $var, array $context = []): void
{
    LoggerFactory::get()->debug($var, $context);
}
