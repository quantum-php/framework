<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth\Enums;

/**
 * Class AuthKeys
 * @package Quantum\Auth
 * @codeCoverageIgnore
 */
final class AuthKeys
{
    /**
     * One time password key
     */
    public const OTP = 'otp';

    /**
     * One time password expiry key
     */
    public const OTP_EXPIRY = 'otpExpiry';

    /**
     * One time password token key
     */
    public const OTP_TOKEN = 'otpToken';

    /**
     * Username key
     */
    public const USERNAME = 'username';

    /**
     * Password key
     */
    public const PASSWORD = 'password';

    /**
     * Access token key
     */
    public const ACCESS_TOKEN = 'accessToken';

    /**
     * Refresh token key
     */
    public const REFRESH_TOKEN = 'refreshToken';

    /**
     * Activation token key
     */
    public const ACTIVATION_TOKEN = 'activationToken';

    /**
     * Reset token key
     */
    public const RESET_TOKEN = 'resetToken';

    /**
     * Remember token key
     */
    public const REMEMBER_TOKEN = 'rememberToken';

    private function __construct()
    {
    }
}
