<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\App\Enums\ReservedKeys;
use Quantum\Http\Enums\ContentType;
use Quantum\Http\Enums\StatusCode;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Di\Di;

/**
 * Gets the Request instance from DI
 */
function request(): Request
{
    if (!Di::isRegistered(Request::class)) {
        Di::register(Request::class);
    }

    return Di::get(Request::class);
}

/**
 * Gets the Response instance from DI
 */
function response(): Response
{
    if (!Di::isRegistered(Response::class)) {
        Di::register(Response::class);
    }

    return Di::get(Response::class);
}

/**
 * Gets the base url
 * @throws DiException
 * @throws ReflectionException
 */
function base_url(bool $withModulePrefix = false): string
{
    return request()->getBaseUrl($withModulePrefix);
}

/**
 * Gets the current url
 * @throws DiException|ReflectionException
 */
function current_url(): string
{
    return request()->getCurrentUrl();
}

/**
 * Redirect
 * @throws DiException|ReflectionException
 */
function redirect(string $url, int $code = StatusCode::FOUND): Response
{
    return response()->redirect($url, $code);
}

/**
 * Redirect with data
 * @param array<string, mixed> $data
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function redirectWith(string $url, array $data, int $code = StatusCode::FOUND): Response
{
    session()->set(ReservedKeys::PREV_REQUEST, $data);
    return response()->redirect($url, $code);
}

/**
 * Gets old input values after redirect
 * @return mixed|null
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function old(string $key)
{
    if (!session()->has(ReservedKeys::PREV_REQUEST)) {
        return null;
    }

    $prevRequest = session()->get(ReservedKeys::PREV_REQUEST);

    if (!is_array($prevRequest) || !isset($prevRequest[$key])) {
        return null;
    }

    $value = $prevRequest[$key];
    unset($prevRequest[$key]);

    session()->set(ReservedKeys::PREV_REQUEST, $prevRequest);

    return $value;
}

/**
 * Gets the referrer
 * @throws DiException|ReflectionException
 */
function get_referrer(): ?string
{
    return request()->getReferrer();
}

/**
 * Handles page not found
 * @throws ConfigException|DiException|BaseException|ReflectionException
 */
function page_not_found_response(): Response
{
    $acceptHeader = request()->getHeader('Accept');

    $isJson = $acceptHeader === ContentType::JSON;

    if ($isJson) {
        return response()->json(
            ['status' => 'error', 'message' => 'Page not found',],
            StatusCode::NOT_FOUND
        );
    }

    return response()->html(
        partial('errors' . DS . StatusCode::NOT_FOUND),
        StatusCode::NOT_FOUND
    );
}
