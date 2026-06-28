<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Enums;

/**
 * Class ReservedKeys
 * @package Quantum\App
 */
final class ReservedKeys
{
    /**
     * Internal response key for rendered view
     */
    public const RENDERED_VIEW = '_qt_rendered_view';

    /**
     * Internal session key for previous request
     */
    public const PREV_REQUEST = '_qt_prev_request';

}
