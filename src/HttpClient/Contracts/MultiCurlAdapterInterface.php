<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Contracts;

/**
 * Interface MultiCurlAdapterInterface
 * @package Quantum\HttpClient
 */
interface MultiCurlAdapterInterface extends HttpClientAdapterInterface
{
    /**
     * Registers complete callback
     */
    public function complete(callable $callback): self;

    /**
     * Registers success callback
     */
    public function success(callable $callback): self;

    /**
     * Registers error callback
     */
    public function error(callable $callback): self;

    /**
     * Adds GET request
     * @param array<string, mixed> $data
     * @return mixed
     */
    public function addGet(string $url, array $data = []);

    /**
     * Adds POST request
     * @param mixed $data
     * @return mixed
     */
    public function addPost(string $url, $data = '', bool $follow_303_with_post = false);

}
