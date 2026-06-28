<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

/**
 * Class BaseController
 * @package Modules\{{MODULE_NAME}}
 */
abstract class BaseController
{

    /**
     * Status error
     */
    const STATUS_ERROR = 'error';

    /**
     * Status success
     */
    const STATUS_SUCCESS = 'success';

    /**
     * CSRF verification
     */
    public bool $csrfVerification = false;
}

