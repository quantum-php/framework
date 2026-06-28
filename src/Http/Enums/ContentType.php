<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Enums;

/**
 * Class ContentType
 * @package Quantum\Http
 * @codeCoverageIgnore
 */
final class ContentType
{
    /**
     * HTML content type
     */
    public const HTML = 'text/html';

    /**
     * XML content type
     */
    public const XML = 'application/xml';

    /**
     * JSON content type
     */
    public const JSON = 'application/json';

    /**
     * JSONP content type
     */
    public const JSONP = 'application/javascript';

    /**
     * Multipart form data
     */
    public const FORM_DATA = 'multipart/form-data';

    /**
     * URL encoded
     */
    public const URL_ENCODED = 'application/x-www-form-urlencoded';

    /**
     * Default content type for binary streams
     */
    public const OCTET_STREAM = 'application/octet-stream';

    private function __construct()
    {
    }
}
