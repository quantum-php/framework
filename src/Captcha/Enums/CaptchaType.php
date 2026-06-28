<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Captcha\Enums;

/**
 * Class CaptchaType
 * @package Quantum\Captcha
 * @codeCoverageIgnore
 */
final class CaptchaType
{
    public const HCAPTCHA = 'hcaptcha';

    public const RECAPTCHA = 'recaptcha';

    private function __construct()
    {
    }
}
