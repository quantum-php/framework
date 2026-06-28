<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Request;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Enums\ContentType;
use ReflectionException;

/**
 * Trait Params
 * @package Quantum\Http
 */
trait Params
{
    /**
     * Request content type
     * @var string|null
     */
    private ?string $__contentType = null;

    /**
     * Gets the GET params.
     * @return array<string, mixed>
     */
    private function getParams(): array
    {
        if ($_GET === []) {
            return [];
        }

        return filter_input_array(INPUT_GET) ?: [];
    }

    /**
     * Gets the POST params.
     * @return array<string, mixed>
     */
    private function postParams(): array
    {
        if ($_POST === []) {
            return [];
        }

        return filter_input_array(INPUT_POST) ?: [];
    }

    /**
     * Parses and returns JSON payload parameters.
     * @return array<string, mixed>
     */
    private function jsonPayloadParams(): array
    {
        if (
            !in_array($this->__method, ['PUT', 'PATCH', 'POST'], true) ||
            $this->__contentType !== ContentType::JSON
        ) {
            return [];
        }

        return json_decode($this->getRawInput(), true) ?: [];
    }

    /**
     * Parses and returns URL-encoded parameters.
     * @return array<string, mixed>
     */
    private function urlEncodedParams(): array
    {
        if (
            !in_array($this->__method, ['PUT', 'PATCH', 'POST'], true) ||
            $this->__contentType !== ContentType::URL_ENCODED
        ) {
            return [];
        }

        parse_str(urldecode($this->getRawInput()), $result);

        return $result; /** @phpstan-ignore return.type */
    }

    /**
     * Parses and returns multipart form data parameters.
     * @return array<string, mixed>
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    private function getRawInputParams(): array
    {
        if (
            !in_array($this->__method, ['PUT', 'PATCH', 'POST'], true) ||
            $this->__contentType !== ContentType::FORM_DATA
        ) {
            return ['params' => [], 'files' => []];
        }

        return $this->parse($this->getRawInput());
    }

    /**
     * Retrieves the raw HTTP request body as a string.
     */
    private function getRawInput(): string
    {
        return file_get_contents('php://input') ?: '';
    }
}
