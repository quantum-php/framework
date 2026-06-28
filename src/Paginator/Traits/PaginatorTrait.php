<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator\Traits;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\Paginator\Enums\Pagination;
use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Trait PaginatorTrait
 * @package Quantum\Paginator
 */
trait PaginatorTrait
{
    protected string $baseUrl;

    protected int $total;

    protected int $perPage;

    protected int $page;

    /**
     * @throws DiException
     * @throws ReflectionException
     */
    protected function initialize(int $perPage, int $page = 1): void
    {
        $this->baseUrl = base_url();
        $this->perPage = $perPage;
        $this->page = $page;
    }

    /**
     * Get the total number of items
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Get the current page number
     */
    public function currentPageNumber(): int
    {
        return $this->page;
    }

    /**
     * Get previous page number
     */
    public function previousPageNumber(): ?int
    {
        if ($this->page > 1) {
            return $this->page - 1;
        }

        if ($this->page == 1) {
            return $this->page;
        }

        return null;
    }

    /**
     * Get next page number
     */
    public function nextPageNumber(): ?int
    {
        if ($this->page < $this->lastPageNumber()) {
            return $this->page + 1;
        }

        if ($this->page == $this->lastPageNumber()) {
            return $this->page;
        }

        return null;
    }

    /**
     * Get last page number
     */
    public function lastPageNumber(): int
    {
        return (int) ceil($this->total() / $this->perPage);
    }

    /**
     * Get the current page link
     * @throws DiException|ReflectionException
     */
    public function currentPageLink(bool $withBaseUrl = false): ?string
    {
        return $this->getPageLink($this->page, $withBaseUrl);
    }

    /**
     * Get first page link
     * @throws DiException|ReflectionException
     */
    public function firstPageLink(bool $withBaseUrl = false): ?string
    {
        return $this->getPageLink(Pagination::FIRST_PAGE_NUMBER, $withBaseUrl);
    }

    /**
     * Get previous page link
     * @throws DiException|ReflectionException
     */
    public function previousPageLink(bool $withBaseUrl = false): ?string
    {
        return $this->getPageLink($this->previousPageNumber(), $withBaseUrl);
    }

    /**
     * Get next page link
     * @throws DiException|ReflectionException
     */
    public function nextPageLink(bool $withBaseUrl = false): ?string
    {
        return $this->getPageLink($this->nextPageNumber(), $withBaseUrl);
    }

    /**
     * Get last page link
     * @throws DiException|ReflectionException
     */
    public function lastPageLink(bool $withBaseUrl = false): ?string
    {
        return $this->getPageLink($this->lastPageNumber(), $withBaseUrl);
    }

    /**
     * Get items per page
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get all page links
     * @return array<int, string|null>
     * @throws DiException|ReflectionException
     */
    public function links(bool $withBaseUrl = false): array
    {
        $links = [];

        for ($i = 1; $i <= $this->lastPageNumber(); $i++) {
            $links[] = $this->getPageLink($i, $withBaseUrl);
        }

        return $links;
    }

    /**
     * Get pagination HTML
     * @throws ConfigException|DiException|LangException|ReflectionException
     */
    public function getPagination(bool $withBaseUrl = false, ?int $pageItemsCount = null): ?string
    {
        $totalPages = $this->lastPageNumber();
        $currentPage = $this->currentPageNumber();

        if ($totalPages <= 1) {
            return null;
        }

        $pageItemsCount = max(Pagination::MINIMUM_PAGE_ITEMS_COUNT, $pageItemsCount ?? Pagination::MINIMUM_PAGE_ITEMS_COUNT);

        $pagination = ['<ul class="' . Pagination::PAGINATION_CLASS . '">'];

        if ($currentPage > 1) {
            $pagination[] = $this->getPreviousPageItem($this->previousPageLink());
        }

        if ($pageItemsCount) {
            $links = $this->links($withBaseUrl);

            [$startPage, $endPage] = $this->calculateStartEndPages($currentPage, $totalPages, $pageItemsCount);

            $pagination[] = $this->addFirstPageLink($startPage);
            $pagination[] = $this->getItemsLinks($startPage, $endPage, $currentPage, $links);
            $pagination[] = $this->addLastPageLink($endPage, $totalPages, $links);
        }

        if ($currentPage < $totalPages) {
            $pagination[] = $this->getNextPageItem($this->nextPageLink());
        }

        $pagination[] = '</ul>';

        return implode('', $pagination);
    }

    /**
     * Get the URI for pagination
     * @throws DiException|ReflectionException
     */
    protected function getUri(bool $withBaseUrl = false): string
    {
        $routeUrl = preg_replace('/([?&](page|per_page)=\d+)/', '', request()->getUri() ?? '');
        $routeUrl = preg_replace('/&/', '?', $routeUrl ?? '', 1);
        $url = $routeUrl;

        if ($withBaseUrl) {
            $url = $this->baseUrl . $routeUrl;
        }

        $delimiter = str_contains($url ?? '', '?') ? '&' : '?';

        return $url . $delimiter;
    }

    /**
     * Get page link
     * @throws DiException|ReflectionException
     */
    protected function getPageLink(?int $pageNumber, bool $withBaseUrl = false): ?string
    {
        if ($pageNumber !== null && $pageNumber !== 0) {
            return $this->getUri($withBaseUrl) . Pagination::PER_PAGE . '=' . $this->perPage . '&' . Pagination::PAGE . '=' . $pageNumber;
        }

        return null;
    }

    /**
     * Get next page item HTML
     * @throws LoaderException|ConfigException|DiException|ReflectionException|LangException
     */
    protected function getNextPageItem(?string $nextPageLink): string
    {
        $link = [];

        if (!in_array($nextPageLink, [null, '', '0'], true)) {
            $link[] = '<li><a href="' . $nextPageLink . '">' . t('common.pagination.next') . '</a></li>';
        }

        return implode('', $link);
    }

    /**
     *
     * @param string|null $previousPageLink
     * @return string
     */
    /**
     * Get previous page item HTML
     * @throws LoaderException|ConfigException|DiException|LangException|ReflectionException
     */
    protected function getPreviousPageItem(?string $previousPageLink): string
    {
        $link = [];

        if (!in_array($previousPageLink, [null, '', '0'], true)) {
            $link[] = '<li><a href="' . $previousPageLink . '">' . t('common.pagination.prev') . '</a></li>';
        }

        return implode('', $link);
    }

    /**
     * Get items links HTML
     * @param array<int, string|null> $links
     */
    protected function getItemsLinks(int $startPage, int $endPage, int $currentPage, array $links): string
    {
        $pagination = [];

        for ($i = $startPage; $i <= $endPage; $i++) {
            $active = $i === $currentPage ? 'class="' . Pagination::PAGINATION_CLASS_ACTIVE . '"' : '';
            $pagination[] = '<li ' . $active . '><a href="' . $links[$i - 1] . '">' . $i . '</a></li>';
        }

        return implode('', $pagination);
    }

    /**
     * Calculate start and end pages
     * @return array{0: int, 1: int}
     */
    protected function calculateStartEndPages(int $currentPage, int $totalPages, int $pageItemsCount): array
    {
        $startPage = (int) max(1, $currentPage - ceil(($pageItemsCount - Pagination::EDGE_PADDING) / 2));
        $endPage = (int) min($totalPages, $startPage + $pageItemsCount - Pagination::EDGE_PADDING);

        return [$startPage, $endPage];
    }

    /**
     * Add first page link HTML
     */
    protected function addFirstPageLink(int $startPage): string
    {
        $pagination = [];

        if ($startPage > 1) {
            $pagination[] = '<li><a href="' . $this->firstPageLink() . '">' . Pagination::FIRST_PAGE_NUMBER . '</a></li>';
            if ($startPage > 2) {
                $pagination[] = '<li><span>...</span></li>';
            }
        }

        return implode('', $pagination);
    }

    /**
     * Add last page link HTML
     * @param array<int, string|null> $links
     */
    protected function addLastPageLink(int $endPage, int $totalPages, array $links): string
    {
        $pagination = [];

        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $pagination[] = '<li><span>...</span></li>';
            }
            $pagination[] = '<li><a href="' . $links[$totalPages - 1] . '">' . $totalPages . '</a></li>';
        }

        return implode('', $pagination);
    }
}
