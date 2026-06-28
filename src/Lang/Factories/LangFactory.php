<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang\Factories;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Lang\Translator;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Lang\Lang;
use Quantum\Di\Di;

/**
 * Class LangFactory
 * @package Quantum\Lang
 */
class LangFactory
{
    private ?Lang $instance = null;

    /**
     * @throws LangException|ConfigException|LoaderException|DiException|ReflectionException
     */
    public static function get(): Lang
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve();
    }

    /**
     * @throws LangException|ConfigException|DiException|ReflectionException|LoaderException
     */
    public function resolve(): Lang
    {
        if ($this->instance !== null) {
            return $this->instance;
        }

        [$isEnabled, $supported, $default] = $this->loadLangConfig();

        $lang = $this->detectLanguage($supported, $default);

        $translator = new Translator($lang);

        return $this->instance = new Lang($lang, $isEnabled, $translator);
    }

    /**
     * @return array{0: bool, 1: array<string>, 2: string}
     * @throws LoaderException|ConfigException|DiException|ReflectionException
     */
    private function loadLangConfig(): array
    {
        if (!config()->has('lang')) {
            config()->import(new Setup('config', 'lang'));
        }

        return [
            filter_var(config()->get('lang.enabled'), FILTER_VALIDATE_BOOLEAN),
            (array) config()->get('lang.supported'),
            config()->get('lang.default'),
        ];
    }

    /**
     * @param array<string> $supported
     * @throws LangException|DiException|ReflectionException
     */
    private function detectLanguage(array $supported, ?string $default): string
    {
        $lang = $this->getLangFromQuery($supported);

        if (in_array($lang, [null, '', '0'], true)) {
            $lang = $this->getLangFromUrlSegment($supported);
        }

        if (in_array($lang, [null, '', '0'], true)) {
            $lang = $this->getLangFromHeader($supported);
        }

        if (in_array($lang, [null, '', '0'], true)) {
            $lang = $default;
        }

        if (!$lang) {
            throw LangException::misconfiguredDefaultConfig();
        }

        return $lang;
    }

    /**
     * @param array<string> $supported
     */
    private function getLangFromQuery(array $supported): ?string
    {
        $queryLang = request()->getQueryParam('lang');

        return $queryLang && in_array($queryLang, $supported) ? $queryLang : null;
    }

    /**
     * @param array<string> $supported
     * @throws DiException|ReflectionException
     */
    private function getLangFromUrlSegment(array $supported): ?string
    {
        $segmentIndex = (int) config()->get('lang.url_segment');

        if (!in_array(request()->getRoutePrefix(), [null, '', '0'], true) && $segmentIndex === 1) {
            $segmentIndex++;
        }

        $segmentLang = request()->getSegment($segmentIndex);

        return $segmentLang && in_array($segmentLang, $supported) ? $segmentLang : null;
    }

    /**
     * @param array<string> $supported
     * @throws DiException|ReflectionException
     */
    private function getLangFromHeader(array $supported): ?string
    {
        $acceptedLang = server()->acceptedLang();

        return $acceptedLang && in_array($acceptedLang, $supported) ? $acceptedLang : null;
    }
}
