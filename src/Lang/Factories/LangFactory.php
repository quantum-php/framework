<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang\Factories;

use Quantum\Lang\Contracts\LangAdapterInterface;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Lang\Adapters\FileAdapter;
use Quantum\Di\Exceptions\DiException;
use Quantum\Lang\Enums\LangType;
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
    public const ADAPTERS = [
        LangType::FILE => FileAdapter::class,
    ];

    /**
     * @var array<string, Lang>
     */
    private array $instances = [];

    /**
     * @throws LangException|ConfigException|LoaderException|DiException|ReflectionException
     */
    public static function get(?string $adapter = null): Lang
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve($adapter);
    }

    /**
     * @throws LangException|ConfigException|DiException|ReflectionException|LoaderException|BaseException
     */
    public function resolve(?string $adapter = null): Lang
    {
        if (!config()->has('lang')) {
            config()->import(new Setup('config', 'lang'));
        }

        $isEnabled = filter_var(config()->get('lang.enabled'), FILTER_VALIDATE_BOOLEAN);
        $supported = (array) config()->get('lang.supported');
        $default = config()->get('lang.default_locale');
        $adapter ??= config()->get('lang.default');

        $lang = $this->detectLanguage($supported, $default);

        if (!isset($this->instances[$adapter])) {
            $adapterClass = $this->getAdapterClass($adapter);
            $this->instances[$adapter] = new Lang($lang, $isEnabled, $this->createAdapter($adapterClass, $lang, $adapter));
        }

        return $this->instances[$adapter];
    }

    /**
     * @throws BaseException
     */
    private function getAdapterClass(string $adapter): string
    {
        if (!array_key_exists($adapter, self::ADAPTERS)) {
            throw LangException::adapterNotSupported($adapter);
        }

        return self::ADAPTERS[$adapter];
    }

    /**
     * @throws BaseException
     */
    private function createAdapter(string $adapterClass, string $lang, string $adapter): LangAdapterInterface
    {
        $langAdapter = new $adapterClass($lang);

        if (!$langAdapter instanceof LangAdapterInterface) {
            throw LangException::adapterNotSupported($adapter);
        }

        return $langAdapter;
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
