<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Enums;

/**
 * Enum ReservedKeys
 * @package Quantum\App
 */
enum ReservedKeys: string
{
    /**
     * Internal response key for rendered view
     */
    case RENDERED_VIEW = '_qt_rendered_view';

    /**
     * Internal session key for previous request
     */
    case PREV_REQUEST = '_qt_prev_request';
}
