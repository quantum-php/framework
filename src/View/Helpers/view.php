<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use League\CommonMark\Exception\CommonMarkException;
use Quantum\Config\Exceptions\ConfigException;
use League\CommonMark\CommonMarkConverter;
use Quantum\App\Exceptions\BaseException;
use Quantum\View\Factories\ViewFactory;
use Quantum\Di\Exceptions\DiException;
use Quantum\View\RawParam;
use Quantum\View\View;

/**
 * Gets the View instance
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function view(): View
{
    return ViewFactory::get();
}

/**
 * Rendered partial
 * @param array<string, mixed> $args
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function partial(string $partial, array $args = []): string
{
    return view()->renderPartial($partial, $args);
}

/**
 * Gets the param passed to view
 * @return mixed|null
 * @throws BaseException
 * @throws ConfigException
 * @throws DiException
 * @throws ReflectionException
 */
function view_param(string $key)
{
    return view()->getParam($key);
}

/**
 * Creates a raw param
 * @param mixed $value
 */
function raw_param($value): RawParam
{
    return new RawParam($value);
}

/**
 * @throws CommonMarkException
 */
function markdown_to_html(string $content, bool $sanitize = false): string
{
    $converter = new CommonMarkConverter();
    $purifier = null;

    if ($sanitize) {
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
    }

    $html = (string) $converter->convertToHtml($content);

    if ($purifier instanceof HTMLPurifier) {
        return $purifier->purify($html);
    }

    return $html;
}
