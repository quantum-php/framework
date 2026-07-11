<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang;

use Quantum\Lang\Contracts\LangAdapterInterface;

/**
 * Class Lang
 * @package Quantum\Lang
 */
class Lang
{
    private ?string $currentLang = null;

    private LangAdapterInterface $adapter;

    private bool $isEnabled;

    public function __construct(string $lang, bool $isEnabled, LangAdapterInterface $adapter)
    {
        $this->isEnabled = $isEnabled;
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

    /**
     * Is multilang enabled
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getAdapter(): LangAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Load translations
     */
    public function load(): void
    {
        $this->adapter->loadTranslations();
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
     * Flush loaded translations
     */
    public function flush(): void
    {
        $this->adapter->flush();
    }
}
