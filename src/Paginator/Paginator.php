<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator;

use Quantum\Paginator\Exceptions\PaginatorException;
use Quantum\Paginator\Contracts\PaginatorInterface;
use Quantum\App\Exceptions\BaseException;

/**
 * Class Paginator
 * @package Quantum\Paginator
 * @method mixed data()
 * @method mixed firstItem()
 * @method mixed lastItem()
 * @method int currentPageNumber()
 * @method int|null previousPageNumber()
 * @method int|null nextPageNumber()
 * @method int lastPageNumber()
 * @method string|null currentPageLink(bool $withBaseUrl = false)
 * @method string|null firstPageLink(bool $withBaseUrl = false)
 * @method string|null previousPageLink(bool $withBaseUrl = false)
 * @method string|null nextPageLink(bool $withBaseUrl = false)
 * @method string|null lastPageLink(bool $withBaseUrl = false)
 * @method int perPage()
 * @method int total()
 * @method list<string|null> links(bool $withBaseUrl = false)
 * @method string|null getPagination(bool $withBaseUrl = false, ?int $pageItemsCount = null)
 */
class Paginator
{
    private PaginatorInterface $adapter;

    public function __construct(PaginatorInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Get the adapter instance
     */
    public function getAdapter(): PaginatorInterface
    {
        return $this->adapter;
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     * @throws BaseException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists($this->adapter, $method)) {
            throw PaginatorException::methodNotSupported($method, $this->adapter::class);
        }

        return $this->adapter->$method(...$arguments);
    }
}
