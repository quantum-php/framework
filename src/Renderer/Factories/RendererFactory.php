<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Renderer\Factories;

use Quantum\Renderer\Contracts\TemplateRendererInterface;
use Quantum\Renderer\Exceptions\RendererException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Renderer\Adapters\HtmlAdapter;
use Quantum\Renderer\Adapters\TwigAdapter;
use Quantum\App\Exceptions\BaseException;
use Quantum\Renderer\Enums\RendererType;
use Quantum\Di\Exceptions\DiException;
use Quantum\Renderer\Renderer;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class RendererFactory
 * @package Quantum\Renderer
 */
class RendererFactory
{
    public const ADAPTERS = [
        RendererType::HTML => HtmlAdapter::class,
        RendererType::TWIG => TwigAdapter::class,
    ];

    /**
     * @var array<string, Renderer>
     */
    private array $instances = [];

    /**
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public static function get(?string $adapter = null): Renderer
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve($adapter);
    }

    /**
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public function resolve(?string $adapter = null): Renderer
    {
        if (!config()->has('view')) {
            config()->import(new Setup('config', 'view'));
        }

        $adapter ??= config()->get('view.default');

        $adapterClass = $this->getAdapterClass($adapter);

        if (!isset($this->instances[$adapter])) {
            $this->instances[$adapter] = $this->createInstance($adapterClass, $adapter);
        }

        return $this->instances[$adapter];
    }

    /**
     * @throws RendererException|BaseException|ReflectionException
     */
    private function createInstance(string $adapterClass, string $adapter): Renderer
    {
        $adapterInstance = new $adapterClass(config()->get('view.' . $adapter));

        if (!$adapterInstance instanceof TemplateRendererInterface) {
            throw RendererException::adapterNotSupported($adapter);
        }

        return new Renderer($adapterInstance);
    }

    /**
     * @throws BaseException
     */
    private function getAdapterClass(string $adapter): string
    {
        if (!array_key_exists($adapter, self::ADAPTERS)) {
            throw RendererException::adapterNotSupported($adapter);
        }

        return self::ADAPTERS[$adapter];
    }
}
