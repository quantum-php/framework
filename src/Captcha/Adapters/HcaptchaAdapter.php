<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Captcha\Adapters;

use Quantum\Captcha\Contracts\CaptchaInterface;
use Quantum\Captcha\Traits\CaptchaTrait;
use Quantum\HttpClient\HttpClient;

/**
 * Class HcaptchaAdapter
 * @package Quantum\Captcha
 */
class HcaptchaAdapter implements CaptchaInterface
{
    use CaptchaTrait;

    public const VERIFY_URL = 'https://hcaptcha.com/siteverify';

    public const CLIENT_API = 'https://hcaptcha.com/1/api.js?onload=onLoadCallback&recaptchacompat=off';

    protected string $name = 'h-captcha';

    /**
     * @var string[]
     */
    protected $elementClasses = ['h-captcha'];

    /**
     * Hcaptcha constructor
     * @param array<string, mixed> $params
     */
    public function __construct(array $params, HttpClient $httpClient)
    {
        $this->http = $httpClient;

        $this->secretKey = $params['secret_key'];
        $this->siteKey = $params['site_key'];
        $this->type = $params['type'] ?? null;
    }
}
