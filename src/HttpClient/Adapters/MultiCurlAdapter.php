<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Adapters;

use Quantum\HttpClient\Contracts\MultiCurlAdapterInterface;
use Curl\MultiCurl;
use Curl\Curl;

/**
 * Class MultiCurlAdapter
 * @package Quantum\HttpClient
 */
class MultiCurlAdapter implements MultiCurlAdapterInterface
{
    private MultiCurl $client;

    public function __construct(?MultiCurl $client = null)
    {
        $this->client = $client ?? new MultiCurl();
    }

    public function complete(callable $callback): MultiCurlAdapterInterface
    {
        $this->client->complete(function (Curl $instance) use ($callback): void {
            $callback(new CurlAdapter($instance));
        });

        return $this;
    }

    public function success(callable $callback): MultiCurlAdapterInterface
    {
        $this->client->success($callback);

        return $this;
    }

    public function error(callable $callback): MultiCurlAdapterInterface
    {
        $this->client->error($callback);

        return $this;
    }

    /**
     * @return mixed
     */
    public function start()
    {
        return $this->client->start();
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed
     */
    public function addGet(string $url, array $data = [])
    {
        return $this->wrapCurlResult($this->client->addGet($url, $data));
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function addPost(string $url, $data = '', bool $follow_303_with_post = false)
    {
        return $this->wrapCurlResult($this->client->addPost($url, $data, $follow_303_with_post));
    }

    /**
     * @param mixed $value
     */
    public function setHeader(string $key, $value): MultiCurlAdapterInterface
    {
        $this->client->setHeader($key, $value);

        return $this;
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    public function setHeaders(array $headers): MultiCurlAdapterInterface
    {
        $this->client->setHeaders($headers);

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setOpt(int $option, $value): MultiCurlAdapterInterface
    {
        $this->client->setOpt($option, $value);

        return $this;
    }

    /**
     * @param array<int, mixed> $options
     */
    public function setOpts(array $options): MultiCurlAdapterInterface
    {
        $this->client->setOpts($options);

        return $this;
    }

    public function supportsMethod(string $method): bool
    {
        return method_exists($this->client, $method);
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function callMethod(string $method, array $arguments)
    {
        return $this->client->$method(...$arguments);
    }

    /**
     * @param mixed $result
     * @return mixed
     */
    private function wrapCurlResult($result)
    {
        return $result instanceof Curl ? new CurlAdapter($result) : $result;
    }
}
