<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Captcha\Factories;

use Quantum\Captcha\Exceptions\CaptchaException;
use Quantum\Captcha\Contracts\CaptchaInterface;
use Quantum\Captcha\Adapters\RecaptchaAdapter;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Captcha\Adapters\HcaptchaAdapter;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Captcha\Enums\CaptchaType;
use Quantum\HttpClient\HttpClient;
use Quantum\Captcha\Captcha;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class CaptchaFactory
 * @package Quantum\Captcha
 */
class CaptchaFactory
{
    public const ADAPTERS = [
        CaptchaType::HCAPTCHA => HcaptchaAdapter::class,
        CaptchaType::RECAPTCHA => RecaptchaAdapter::class,
    ];

    /**
     * @var array<string, Captcha>
     */
    private array $instances = [];

    /**
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public static function get(?string $adapter = null): Captcha
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve($adapter);
    }

    /**
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public function resolve(?string $adapter = null): Captcha
    {
        if (!config()->has('captcha')) {
            config()->import(new Setup('config', 'captcha'));
        }

        $adapter ??= config()->get('captcha.default');

        $adapterClass = $this->getAdapterClass($adapter);

        if (!isset($this->instances[$adapter])) {
            $this->instances[$adapter] = $this->createInstance($adapterClass, $adapter);
        }

        return $this->instances[$adapter];
    }

    /**
     * @throws CaptchaException|BaseException
     */
    private function createInstance(string $adapterClass, string $adapter): Captcha
    {
        $adapterInstance = new $adapterClass(config()->get('captcha.' . $adapter), new HttpClient());

        if (!$adapterInstance instanceof CaptchaInterface) {
            throw CaptchaException::adapterNotSupported($adapter);
        }

        return new Captcha($adapterInstance);
    }

    /**
     * @throws BaseException
     */
    private function getAdapterClass(string $adapter): string
    {
        if (!array_key_exists($adapter, self::ADAPTERS)) {
            throw CaptchaException::adapterNotSupported($adapter);
        }

        return self::ADAPTERS[$adapter];
    }
}
