<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang;

use Quantum\Lang\Contracts\LangAdapterInterface;
use Quantum\Lang\Exceptions\LangException;
use Quantum\App\Exceptions\BaseException;

/**
 * Class Lang
 * @package Quantum\Lang
 */
class Lang
{
    private ?string $currentLang = null;

    private LangAdapterInterface $adapter;

    public function __construct(string $lang, LangAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->setLang($lang);
    }

    /**
     * Set the current language
     * @return $this
     */
    public function setLang(string $lang): self
    {
        $this->currentLang = $lang;
        $this->adapter->setLang($lang);
        return $this;
    }

    /**
     * Get current language
     */
    public function getLang(): ?string
    {
        return $this->currentLang;
    }

    public function getAdapter(): LangAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Get translation by key
     * @param string|array<int|string, mixed>|null $params
     */
    public function getTranslation(string $key, array|string $params = null): ?string
    {
        return $this->adapter->get($key, $params);
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     * @throws BaseException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists($this->adapter, $method)) {
            throw LangException::methodNotSupported($method, $this->adapter::class);
        }

        return $this->adapter->$method(...$arguments);
    }
}
