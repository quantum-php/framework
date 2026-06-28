<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\View\Factories;

use Quantum\Renderer\Factories\RendererFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\ResourceCache\ViewCache;
use ReflectionException;
use Quantum\View\View;
use Quantum\Di\Di;

/**
 * Class ViewFactory
 * @package Quantum\View
 * @mixin View
 */
class ViewFactory
{
    private ?View $instance = null;

    /**
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public static function get(): View
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve();
    }

    /**
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public function resolve(): View
    {
        if ($this->instance === null) {
            if (!Di::isRegistered(ViewCache::class)) {
                Di::register(ViewCache::class);
            }

            $this->instance = new View(
                RendererFactory::get(),
                asset(),
                debugbar(),
                Di::get(ViewCache::class)
            );
        }

        return $this->instance;
    }
}
