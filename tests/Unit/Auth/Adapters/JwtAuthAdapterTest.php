<?php

namespace Quantum\Tests\Unit\Auth\Adapters;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Auth\Exceptions\AuthException;
use Quantum\Auth\Adapters\JwtAuthAdapter;
use Quantum\Auth\Enums\ExceptionMessages;
use Quantum\Tests\Unit\Auth\AuthTestCase;
use Quantum\Hasher\Hasher;
use Quantum\Jwt\JwtToken;
use ArgumentCountError;
use Quantum\Auth\User;

class JwtAuthAdapterTest extends AuthTestCase
{
    private JwtAuthAdapter $jwtAuth;

    public function setUp(): void
    {
        parent::setUp();

        $this->jwtAuth = $this->createJwtAuth();

        $admin = $this->jwtAuth->signup($this->adminUser);

        $this->jwtAuth->activate($admin->getFieldValue('activation_token'));
    }

    private function createJwtAuth(): JwtAuthAdapter
    {
        $jwt = (new JwtToken())
            ->setLeeway(1)
            ->setClaims([
                'jti' => uniqid(),
                'iss' => 'issuer',
                'aud' => 'audience',
                'iat' => time(),
                'nbf' => time() + 1,
                'exp' => time() + 60,
            ]);

        return new JwtAuthAdapter(
            $this->authService,
            $this->mailer,
            (new Hasher())->setCost(4),
            $jwt,
            config()->section('auth')
        );
    }

    public function tearDown(): void
    {
        self::$users = [];

        $this->jwtAuth->signout();

        parent::tearDown();
    }

    public function testApiAdapterRequiresItsConfig(): void
    {
        $this->expectException(ArgumentCountError::class);

        new JwtAuthAdapter($this->authService, $this->mailer, (new Hasher())->setCost(4), new JwtToken());
    }

    public function testApiAdapterRejectsAnOtpExpiryThatIsNotANumberEvenWithTwoFactorOff(): void
    {
        config()->set('auth.two_fa', false);
        config()->set('auth.otp_expires', 'abc');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The config key `otp_expires` has the value `abc`, which is not a whole number.');

        $this->createJwtAuth();
    }

    public function testApiAdapterConstructor(): void
    {
        $this->assertInstanceOf(JwtAuthAdapter::class, $this->jwtAuth);
    }

    public function testApiSigninIncorrectCredentials(): void
    {
        $this->expectException(AuthException::class);

        $this->expectExceptionMessage(ExceptionMessages::INCORRECT_CREDENTIALS);

        $this->jwtAuth->signin('admin@qt.com', '111111');
    }

    public function testApiSigninCorrectCredentials(): void
    {
        config()->set('auth.two_fa', false);
        $this->jwtAuth = $this->createJwtAuth();

        $this->assertIsArray($this->jwtAuth->signin('admin@qt.com', 'qwerty'));

        $this->assertArrayHasKey('access_token', $this->jwtAuth->signin('admin@qt.com', 'qwerty'));

        $this->assertArrayHasKey('refresh_token', $this->jwtAuth->signin('admin@qt.com', 'qwerty'));
    }

    public function testApiSignOut(): void
    {
        $this->jwtAuth->signin('admin@qt.com', 'qwerty');

        $this->assertTrue($this->jwtAuth->check());

        $this->jwtAuth->signout();

        $this->assertFalse($this->jwtAuth->check());
    }

    public function testApiUser(): void
    {
        $this->jwtAuth->signin('admin@qt.com', 'qwerty');

        $this->assertInstanceOf(User::class, $this->jwtAuth->user());

        $this->assertEquals('admin@qt.com', $this->jwtAuth->user()->getFieldValue('email'));

        $this->assertEquals('admin', $this->jwtAuth->user()->getFieldValue('role'));

        $this->jwtAuth->signout();

        $this->assertNull($this->jwtAuth->user());
    }

    public function testApiCheck(): void
    {
        $this->assertFalse($this->jwtAuth->check());

        $this->jwtAuth->signin('admin@qt.com', 'qwerty');

        $this->assertTrue($this->jwtAuth->check());

        $this->jwtAuth->signout();

        $this->assertFalse($this->jwtAuth->check());
    }

    public function testApiSignupAndSigninWithoutActivation(): void
    {
        $this->expectException(AuthException::class);

        $this->expectExceptionMessage(ExceptionMessages::INACTIVE_ACCOUNT);

        $this->jwtAuth->signup($this->guestUser);

        $this->assertTrue($this->jwtAuth->signin('guest@qt.com', '123456'));
    }

    public function testApiSignupAndActivateAccount(): void
    {
        $user = $this->jwtAuth->signup($this->guestUser);

        $this->jwtAuth->activate($user->getFieldValue('activation_token'));

        $this->assertIsArray($this->jwtAuth->signin('guest@qt.com', '123456'));

        $this->assertArrayHasKey('access_token', $this->jwtAuth->signin('guest@qt.com', '123456'));

        $this->assertArrayHasKey('refresh_token', $this->jwtAuth->signin('guest@qt.com', '123456'));
    }

    public function testApiForgetReset(): void
    {
        $resetToken = $this->jwtAuth->forget('admin@qt.com');

        $this->jwtAuth->reset($resetToken, '123456789');

        $this->assertIsArray($this->jwtAuth->signin('admin@qt.com', '123456789'));

        $this->assertArrayHasKey('access_token', $this->jwtAuth->signin('admin@qt.com', '123456789'));

        $this->assertArrayHasKey('refresh_token', $this->jwtAuth->signin('admin@qt.com', '123456789'));
    }

    public function testApiVerifyOtp(): void
    {
        config()->set('auth.two_fa', true);
        config()->set('auth.otp_expires', 2);
        $this->jwtAuth = $this->createJwtAuth();

        $otp_token = $this->jwtAuth->signin('admin@qt.com', 'qwerty');

        $tokens = $this->jwtAuth->verifyOtp(123456789, $otp_token);

        $this->assertArrayHasKey('access_token', $tokens);

        $this->assertArrayHasKey('refresh_token', $tokens);
    }

    public function testApiSigninWithoutVerification(): void
    {
        config()->set('auth.two_fa', false);
        config()->set('auth.otp_expires', 2);
        $this->jwtAuth = $this->createJwtAuth();

        $this->assertArrayHasKey('access_token', $this->jwtAuth->signin('admin@qt.com', 'qwerty'));

        $this->assertArrayHasKey('refresh_token', $this->jwtAuth->signin('admin@qt.com', 'qwerty'));
    }

    public function testApiSigninWithVerification(): void
    {
        config()->set('auth.two_fa', true);
        config()->set('auth.otp_expires', 2);
        $this->jwtAuth = $this->createJwtAuth();

        $this->assertIsString($this->jwtAuth->signin('admin@qt.com', 'qwerty'));
    }

    public function testApiResendOtp(): void
    {
        config()->set('auth.two_fa', true);
        config()->set('auth.otp_expires', 2);
        $this->jwtAuth = $this->createJwtAuth();

        $otp_token = $this->jwtAuth->signin('admin@qt.com', 'qwerty');

        $this->assertIsString($this->jwtAuth->resendOtp($otp_token));
    }

    public function testApiRefreshUser(): void
    {
        $this->jwtAuth->signin('admin@qt.com', 'qwerty');

        $user = $this->jwtAuth->user();

        $this->assertEquals('Admin', $user->firstname);

        $this->assertEquals('User', $user->lastname);

        $newUserData = [
            'firstname' => 'Super',
            'lastname' => 'Human',
        ];

        $this->authService->update('email', $this->jwtAuth->user()->email, $newUserData);

        $this->jwtAuth->refreshUser($this->jwtAuth->user()->uuid);

        $refreshedUser = $this->jwtAuth->user();

        $this->assertEquals('Super', $refreshedUser->firstname);

        $this->assertEquals('Human', $refreshedUser->lastname);
    }
}
