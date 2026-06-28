<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http;

use Quantum\Http\Traits\Response\Header;
use Quantum\Http\Traits\Response\Status;
use Quantum\Http\Traits\Response\Body;
use Quantum\Http\Enums\StatusCode;
use Exception;

/**
 * Class Response
 * @package Quantum\Http
 */
class Response
{
    use Header;
    use Body;
    use Status;

    /**
     * XML root element
     * @var string
     */
    private string $xmlRoot = '<data></data>';

    /**
     * Callback function
     * @var string
     */
    private string $callbackFunction = '';

    /**
     * Flushes the response header and body
     */
    public function flush(): void
    {
        $this->__statusCode = StatusCode::OK;
        $this->__headers = [];
        $this->__response = [];
        $this->xmlRoot = '<data></data>';
        $this->callbackFunction = '';
    }

    /**
     * Sends all response data to the client and finishes the request.
     * @throws Exception
     */
    public function send(): void
    {
        if (!environment()->isTesting()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        foreach ($this->__headers as $key => $value) {
            header($key . ': ' . $value);
        }

        http_response_code($this->getStatusCode());

        echo $this->getContent();
    }
}
