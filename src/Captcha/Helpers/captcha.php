<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Captcha\Factories\CaptchaFactory;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Captcha\Captcha;

/**
 * @throws ConfigException|BaseException|DiException|ReflectionException
 */
function captcha(?string $adapter = null): Captcha
{
    return CaptchaFactory::get($adapter);
}
