<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang\Adapters;

use Quantum\Lang\Contracts\LangAdapterInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Dflydev\DotAccessData\Data;
use ReflectionException;

class FileAdapter implements LangAdapterInterface
{
    protected string $lang;

    private ?Data $translations = null;

    public function __construct(string $lang)
    {
        $this->lang = $lang;
    }

    public function setLang(string $lang): LangAdapterInterface
    {
        if ($this->lang !== $lang) {
            $this->lang = $lang;
            $this->flush();
        }

        return $this;
    }

    /**
     * @throws LangException|LoaderException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function loadTranslations(): void
    {
        if ($this->translations !== null) {
            return;
        }

        $this->translations = new Data();
        $loaded = false;

        $sharedDir = base_dir() . DS . 'shared' . DS . 'resources' . DS . 'lang' . DS . $this->lang;
        $sharedFiles = fs()->glob($sharedDir . DS . '*.php');

        if (is_array($sharedFiles) && count($sharedFiles)) {
            $this->loadFiles($sharedFiles);
            $loaded = true;
        }

        $moduleDir = modules_dir() . DS . request()->getCurrentModule() . DS . 'resources' . DS . 'lang' . DS . $this->lang;
        $moduleFiles = fs()->glob($moduleDir . DS . '*.php');

        if (is_array($moduleFiles) && count($moduleFiles)) {
            $this->loadFiles($moduleFiles);
            $loaded = true;
        }

        if (!$loaded) {
            throw LangException::translationsNotFound();
        }
    }

    /**
     * @param array<int|string, mixed>|string|null $params
     */
    public function get(string $key, array|string $params = null): string
    {
        if ($this->translations && $this->translations->has($key)) {
            $message = $this->translations->get($key);
            return $params ? _message($message, $params) : $message;
        }

        return $key;
    }

    public function flush(): void
    {
        $this->translations = null;
    }

    /**
     * @param array<string> $files
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    private function loadFiles(array $files): void
    {
        if ($this->translations === null) {
            return;
        }

        foreach ($files as $file) {
            $fileName = fs()->fileName($file);

            $this->translations->import([
                $fileName => fs()->require($file),
            ]);
        }
    }
}
