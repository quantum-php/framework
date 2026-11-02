<?php

namespace Quantum\Tests\Unit\Auth\Enums;

use Quantum\Auth\Enums\AuthKeys;
use PHPUnit\Framework\TestCase;
use BackedEnum;

class AuthKeysTest extends TestCase
{
    public function testAuthKeysIsAStringBackedEnum(): void
    {
        $this->assertTrue(enum_exists(AuthKeys::class));
        $this->assertTrue(is_subclass_of(AuthKeys::class, BackedEnum::class));
    }

    public function testCasesKeepTheirValues(): void
    {
        $this->assertSame([
            'OTP' => 'otp',
            'OTP_EXPIRY' => 'otpExpiry',
            'OTP_TOKEN' => 'otpToken',
            'USERNAME' => 'username',
            'PASSWORD' => 'password',
            'ACCESS_TOKEN' => 'accessToken',
            'REFRESH_TOKEN' => 'refreshToken',
            'ACTIVATION_TOKEN' => 'activationToken',
            'RESET_TOKEN' => 'resetToken',
            'REMEMBER_TOKEN' => 'rememberToken',
        ], array_column(AuthKeys::cases(), 'value', 'name'));
    }
}
