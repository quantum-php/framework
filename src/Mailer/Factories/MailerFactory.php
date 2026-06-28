<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Mailer\Factories;

use Quantum\Mailer\Adapters\SendinblueAdapter;
use Quantum\Mailer\Exceptions\MailerException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Mailer\Adapters\MandrillAdapter;
use Quantum\Mailer\Adapters\SendgridAdapter;
use Quantum\Mailer\Adapters\MailgunAdapter;
use Quantum\Mailer\Adapters\ResendAdapter;
use Quantum\App\Exceptions\BaseException;
use Quantum\Mailer\Adapters\SmtpAdapter;
use Quantum\Di\Exceptions\DiException;
use Quantum\Mailer\Enums\MailerType;
use Quantum\Mailer\Mailer;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Di\Di;

/**
 * class MailerFactory
 * @package Quantum\Mailer
 */
class MailerFactory
{
    public const ADAPTERS = [
        MailerType::SMTP => SmtpAdapter::class,
        MailerType::MAILGUN => MailgunAdapter::class,
        MailerType::MANDRILL => MandrillAdapter::class,
        MailerType::SENDGRID => SendgridAdapter::class,
        MailerType::SENDINBLUE => SendinblueAdapter::class,
        MailerType::RESEND => ResendAdapter::class,
    ];

    /**
     * @var array<string, Mailer>
     */
    private array $instances = [];

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public static function get(?string $adapter = null): Mailer
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve($adapter);
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function resolve(?string $adapter = null): Mailer
    {
        if (!config()->has('mailer')) {
            config()->import(new Setup('config', 'mailer'));
        }

        $adapter ??= config()->get('mailer.default');

        $adapterClass = $this->getAdapterClass($adapter);

        if (!isset($this->instances[$adapter])) {
            $this->instances[$adapter] = $this->createInstance($adapterClass, $adapter);
        }

        return $this->instances[$adapter];
    }

    /**
     * @throws DiException|BaseException|ReflectionException
     */
    private function createInstance(string $adapterClass, string $adapter): Mailer
    {
        $adapterInstance = new $adapterClass(config()->get('mailer.' . $adapter));

        if (!$adapterInstance instanceof MailerInterface) {
            throw MailerException::adapterNotSupported($adapter);
        }

        return new Mailer($adapterInstance);
    }

    /**
     * @throws BaseException
     */
    private function getAdapterClass(string $adapter): string
    {
        if (!array_key_exists($adapter, self::ADAPTERS)) {
            throw MailerException::adapterNotSupported($adapter);
        }

        return self::ADAPTERS[$adapter];
    }
}
