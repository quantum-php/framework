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
 * Class RecaptchaAdapter
 * @package Quantum\Captcha
 */
class RecaptchaAdapter implements CaptchaInterface
{
    use CaptchaTrait;

    public const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public const CLIENT_API = 'https://www.google.com/recaptcha/api.js';

    protected string $name = 'g-recaptcha';

    /**
     * @var string[]
     */
    protected $elementClasses = ['g-recaptcha'];

    /**
     * RecaptchaAdapter constructor
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
