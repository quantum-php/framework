<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Environment\Enums;

/**
 * Class Env
 * @package Quantum\Environment
 * @codeCoverageIgnore
 */
final class Env
{
    /**
     * Production environment - live system used by end users.
     */
    public const PRODUCTION = 'production';

    /**
     * Staging environment - pre-production environment for final testing.
     */
    public const STAGING = 'staging';

    /**
     * Development environment - used by developers for active development.
     */
    public const DEVELOPMENT = 'development';

    /**
     * Testing environment - used for automated tests and quality assurance.
     */
    public const TESTING = 'testing';

    /**
     * Local environment - developer's local machine environment.
     */
    public const LOCAL = 'local';

    private function __construct()
    {
    }
}
