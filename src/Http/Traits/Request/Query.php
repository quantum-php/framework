<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Request;

/**
 * Trait Query
 * @package Quantum\Http
 */
trait Query
{
    /**
     * Query string
     */
    private ?string $__query = null;

    /**
     * Gets the query string
     */
    public function getQuery(): ?string
    {
        return $this->__query;
    }

    /**
     * Sets the query string
     */
    public function setQuery(string $query): void
    {
        $this->__query = $query;
    }

    /**
     * Gets the query param
     */
    public function getQueryParam(string $key): ?string
    {
        if ($this->__query === null) {
            return null;
        }

        $query = explode('&', $this->__query);

        foreach ($query as $items) {
            $item = explode('=', $items);
            if ($item[0] == $key) {
                return $item[1];
            }
        }

        return null;
    }

    /**
     * Sets the query param
     */
    public function setQueryParam(string $key, string $value): void
    {
        $queryParams = $this->__query ? explode('&', $this->__query) : [];
        $queryParams[] = $key . '=' . $value;
        $this->__query = implode('&', $queryParams);
    }
}
