<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\ResourceCache;

use Quantum\ResourceCache\Exceptions\ResourceCacheException;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Storage\FileSystem;
use Quantum\Http\Response;
use Quantum\Loader\Setup;
use ReflectionException;
use voku\helper\HtmlMin;
use Exception;

/**
 * ViewCache class
 * @package Quantum\ResourceCache
 */
class ViewCache
{
    private ?string $cacheDir = null;

    private int $ttl = 300;

    private bool $isEnabled;

    private bool $minification = false;

    private FileSystem $fs;

    /**
     * @throws DiException
     * @throws Exception
     */
    public function __construct()
    {
        $this->isEnabled = filter_var(config()->get('resource_cache'), FILTER_VALIDATE_BOOLEAN);

        $this->fs = FileSystemFactory::get();
    }

    /**
     * @throws ConfigException|DiException|LoaderException|ReflectionException
     */
    public function setup(): void
    {
        if (!config()->has('view_cache')) {
            config()->import(new Setup('config', 'view_cache'));
        }

        $this->cacheDir = $this->getCacheDir();

        $this->ttl = (int) config()->get('view_cache.ttl', $this->ttl);

        $this->minification = filter_var(config()->get('view_cache.minify', $this->minification), FILTER_VALIDATE_BOOLEAN);

        if (!$this->fs->isDirectory($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function getCachedResponse(string $uri): ?Response
    {
        if ($this->isEnabled() && $this->exists($uri)) {
            $cachedContent = $this->get($uri);
            if ($cachedContent !== null) {
                return response()->html($cachedContent);
            }
        }

        return null;
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function set(string $key, string $content): ViewCache
    {
        if ($this->minification) {
            $content = $this->minify($content);
        }

        $this->fs->put($this->getCacheFile($key), $content);

        return $this;
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function get(string $key): ?string
    {
        if (!$this->exists($key)) {
            return null;
        }

        $content = $this->fs->get($this->getCacheFile($key));

        return is_string($content) ? $content : null;
    }

    /**
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function delete(string $key): void
    {
        $cacheFile = $this->getCacheFile($key);

        if ($this->fs->exists($cacheFile)) {
            $this->fs->remove($cacheFile);
        }
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function exists(string $key): bool
    {
        $cacheFile = $this->getCacheFile($key);
        return $this->fs->exists($cacheFile) && !$this->isExpired($cacheFile);
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function enableCaching(bool $state): void
    {
        $this->isEnabled = $state;
    }

    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function enableMinification(bool $state): void
    {
        $this->minification = $state;
    }

    private function isExpired(string $cacheFile): bool
    {
        if (time() > ($this->fs->lastModified($cacheFile) + $this->ttl)) {
            $this->fs->remove($cacheFile);
            return true;
        }

        return false;
    }

    /**
     * @throws DiException|ReflectionException
     */
    private function getCacheDir(): string
    {
        $configCacheDir = config()->get('view_cache.cache_dir', 'cache');

        $viewCacheDir = base_dir() . DS . $configCacheDir . DS . 'views';

        if ($module = request()->getCurrentModule()) {
            $viewCacheDir = base_dir() . DS . $configCacheDir . DS . 'views' . DS . strtolower($module);
        }

        return $viewCacheDir;
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    private function getCacheFile(string $key): string
    {
        return $this->cacheDir . DS . md5($key . session()->getId());
    }

    /**
     * @throws BaseException
     */
    private function minify(string $content): string
    {
        if (!$this->htmlMinifierExists()) {
            throw ResourceCacheException::notFound('Package', 'HtmlMin');
        }

        return (new HtmlMin())->minify($content);
    }

    protected function htmlMinifierExists(): bool
    {
        return class_exists(HtmlMin::class);
    }
}
