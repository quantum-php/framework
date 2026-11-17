<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\Lang\Factories\LangFactory;
use Quantum\Di\Exceptions\DiException;

/**
 * Gets the current lang
 * @throws LangException|ConfigException|DiException|ReflectionException
 */
function current_lang(): ?string
{
    return LangFactory::get()->getLang();
}

/**
 * Gets the index of the URL segment that holds the language: `lang.url_segment`,
 * moved one segment to the right when the matched route has a module prefix
 * @throws ConfigException|DiException|ReflectionException
 */
function lang_url_segment(): int
{
    $segmentIndex = config()->getInt('lang.url_segment');

    if (!in_array(request()->getRoutePrefix(), [null, '', '0'], true) && $segmentIndex === 1) {
        $segmentIndex++;
    }

    return $segmentIndex;
}

/**
 * Gets translation
 * @param array<int|string, mixed>|string|null $params
 * @throws LangException|ConfigException|DiException|ReflectionException
 */
function t(string $key, array|string $params = null): ?string
{
    return LangFactory::get()->getTranslation($key, $params);
}

/**
 * Outputs the translation
 * @param array<int|string, mixed>|string|null $params
 * @throws LangException|ConfigException|DiException|ReflectionException
 */
function _t(string $key, array|string $params = null): void
{
    echo t($key, $params);
}
