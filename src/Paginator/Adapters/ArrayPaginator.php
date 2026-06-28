<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator\Adapters;

use Quantum\Paginator\Contracts\PaginatorInterface;
use Quantum\Paginator\Traits\PaginatorTrait;
use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Class ArrayPaginator
 * @package Quantum\Paginator
 */
class ArrayPaginator implements PaginatorInterface
{
    use PaginatorTrait;

    /**
     * @var array<mixed>
     */
    private array $items;

    /**
     * @param array<mixed> $items
     * @throws DiException|ReflectionException
     */
    public function __construct(array $items, int $perPage, int $page = 1)
    {
        $this->initialize($perPage, $page);

        $this->items = $items;
        $this->total = count($items);
    }

    /**
     * @inheritDoc
     * @return array<mixed>
     */
    public function data(): array
    {
        return array_slice(
            $this->items,
            ($this->page - 1) * $this->perPage,
            $this->perPage
        );
    }

    /**
     * @inheritDoc
     */
    public function firstItem()
    {
        $data = $this->data();

        if ($data === []) {
            return null;
        }

        return reset($data);
    }

    /**
     * @inheritDoc
     */
    public function lastItem()
    {
        $data = $this->data();

        if ($data === []) {
            return null;
        }

        return end($data);
    }
}
