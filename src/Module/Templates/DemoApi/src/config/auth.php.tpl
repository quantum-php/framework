<?php

return [
    /**
     * ---------------------------------------------------------
     * Authentication configurations
     * ---------------------------------------------------------
     */
    'default' => 'jwt',

    'session' => [
        'service' => {{MODULE_NAMESPACE}}\Services\AuthService::class,
        'remember_lifetime' => env_int('REMEMBER_LIFETIME', 2592000),
    ],

    'jwt' => [
        'service' => {{MODULE_NAMESPACE}}\Services\AuthService::class,
        'claims' => [
            'jti' => uniqid(),
            'iss' => 'issuer',
            'aud' => 'audience',
            'iat' => time(),
            'nbf' => time() + 1,
            'exp' => time() + 3600,
        ]
    ],

    /**
     * ---------------------------------------------------------
     * Two-factor authentication
     * ---------------------------------------------------------
     *
     * Enables or disables 2-factor authentication
     */
    'two_fa' => env_bool('TWO_FA', true),

    /**
     * ---------------------------------------------------------
     * OTP expiration
     * ---------------------------------------------------------
     *
     * OTP expires after minutes defined
     */
    'otp_expires' => 2,
];
