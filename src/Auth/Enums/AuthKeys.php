<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth\Enums;

/**
 * Enum AuthKeys
 * @package Quantum\Auth
 * @codeCoverageIgnore
 */
enum AuthKeys: string
{
    /**
     * One time password key
     */
    case OTP = 'otp';

    /**
     * One time password expiry key
     */
    case OTP_EXPIRY = 'otpExpiry';

    /**
     * One time password token key
     */
    case OTP_TOKEN = 'otpToken';

    /**
     * Username key
     */
    case USERNAME = 'username';

    /**
     * Password key
     */
    case PASSWORD = 'password';

    /**
     * Access token key
     */
    case ACCESS_TOKEN = 'accessToken';

    /**
     * Refresh token key
     */
    case REFRESH_TOKEN = 'refreshToken';

    /**
     * Activation token key
     */
    case ACTIVATION_TOKEN = 'activationToken';

    /**
     * Reset token key
     */
    case RESET_TOKEN = 'resetToken';

    /**
     * Remember token key
     */
    case REMEMBER_TOKEN = 'rememberToken';
}
