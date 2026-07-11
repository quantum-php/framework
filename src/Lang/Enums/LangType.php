<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang\Enums;

final class LangType
{
    public const FILE = 'file';

    public const DEEPL = 'deepl';

    public const GOOGLE_TRANSLATE = 'google_translate';

    private function __construct()
    {
    }
}
