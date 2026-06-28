<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Captcha;

use Quantum\Captcha\Exceptions\CaptchaException;
use Quantum\Captcha\Contracts\CaptchaInterface;
use Quantum\App\Exceptions\BaseException;

/**
 * Class Captcha
 * @package Quantum\Captcha
 * @method string getName()
 * @method string|null getType()
 * @method CaptchaInterface setType(string $type)
 * @method mixed addToForm(string $formIdentifier)
 * @method bool verify(string $response)
 * @method string|null getErrorMessage()
 */
class Captcha
{
    private CaptchaInterface $adapter;

    public function __construct(CaptchaInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): CaptchaInterface
    {
        return $this->adapter;
    }

    /**
     * @param array<mixed>|null $arguments
     * @return mixed
     * @throws BaseException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists($this->adapter, $method)) {
            throw CaptchaException::methodNotSupported($method, $this->adapter::class);
        }

        return $this->adapter->$method(...$arguments);
    }
}
