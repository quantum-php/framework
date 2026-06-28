<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Class Lang
 * @package Quantum\Lang
 */
class Lang
{
    private ?string $currentLang = null;

    private Translator $translator;

    private bool $isEnabled;

    public function __construct(string $lang, bool $isEnabled, Translator $translator)
    {
        $this->isEnabled = $isEnabled;
        $this->translator = $translator;
        $this->setLang($lang);
    }

    /**
     * Set the current language
     * @return $this
     */
    public function setLang(string $lang): self
    {
        $this->currentLang = $lang;
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

    /**
     * Load translations
     * @throws LangException|LoaderException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function load(): void
    {
        $this->translator->loadTranslations();
    }

    /**
     * Get translation by key
     * @param array<int|string, mixed>|string|null $params
     */
    public function getTranslation(string $key, $params = null): ?string
    {
        return $this->translator->get($key, $params);
    }

    /**
     * Flush loaded translations
     */
    public function flush(): void
    {
        $this->translator->flush();
    }
}
