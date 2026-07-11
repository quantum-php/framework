<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang\Contracts;

interface LangAdapterInterface
{
    public function setLang(string $lang): LangAdapterInterface;

    /**
     * @param string|array<int|string, mixed>|null $params
     */
    public function get(string $key, array|string $params = null): string;

    public function loadTranslations(): void;

    public function flush(): void;
}
