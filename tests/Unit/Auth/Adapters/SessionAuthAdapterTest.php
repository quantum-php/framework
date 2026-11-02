<?php

namespace Quantum\Tests\Unit\Auth\Adapters;

use Quantum\Auth\Contracts\AuthServiceInterface;
use Quantum\Auth\Adapters\SessionAuthAdapter;
use Quantum\Auth\Exceptions\AuthException;
use Quantum\Auth\Enums\ExceptionMessages;
use Quantum\Tests\Unit\Auth\AuthTestCase;
use Quantum\Hasher\Hasher;
use Quantum\Auth\User;
use Mockery;

class SessionAuthAdapterTest extends AuthTestCase
{
    private SessionAuthAdapter $sessionAuth;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('auth.two_fa', false);

        $this->sessionAuth = $this->createSessionAuth();

        $admin = $this->sessionAuth->signup($this->adminUser);

        $this->sessionAuth->activate($admin->getFieldValue('activation_token'));
    }

    private function createSessionAuth(): SessionAuthAdapter
    {
        return new SessionAuthAdapter(
            $this->authService,
            $this->mailer,
            (new Hasher())->setCost(4),
            (array) config()->get('auth')
        );
    }

    public function tearDown(): void
    {
        self::$users = [];

        $this->sessionAuth->signout();
    }

    public function testWebAdapterConstructor(): void
    {
        $this->assertInstanceOf(SessionAuthAdapter::class, $this->sessionAuth);
    }

    public function testWebAdapterRejectsAUserSchemaWithAMissingKey(): void
    {
        $schema = $this->userSchema;
        unset($schema['otpToken']);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('userSchema')->andReturn($schema);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage(ExceptionMessages::INCORRECT_USER_SCHEMA);

        new SessionAuthAdapter($authService, $this->mailer, (new Hasher())->setCost(4), (array) config()->get('auth'));
    }

    public function testWebSigninIncorrectCredentials(): void
    {
        $this->expectException(AuthException::class);

        $this->expectExceptionMessage(ExceptionMessages::INCORRECT_CREDENTIALS);

        $this->sessionAuth->signin('admin@qt.com', '111111');
    }

    public function testWebSigninCorrectCredentials(): void
    {
        $this->assertTrue($this->sessionAuth->signin('admin@qt.com', 'qwerty'));

        $this->assertTrue($this->sessionAuth->signin('admin@qt.com', 'qwerty', true));
    }

    public function testWebSigninWithRemember(): void
    {
        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);

        session()->delete('auth_user');

        $this->assertTrue($this->sessionAuth->check());
    }

    public function testWebSigninWithRememberSetsCookie(): void
    {
        $this->assertFalse(cookie()->has('remember_token'));

        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);

        $this->assertTrue(cookie()->has('remember_token'));
        $this->assertNotEmpty(cookie()->get('remember_token'));
    }

    public function testWebSigninWithoutRememberDoesNotSetCookie(): void
    {
        $this->sessionAuth->signin('admin@qt.com', 'qwerty');

        $this->assertFalse(cookie()->has('remember_token'));
    }

    public function testWebSignoutRemovesRememberCookie(): void
    {
        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);

        $this->assertTrue(cookie()->has('remember_token'));

        $this->sessionAuth->signout();

        $this->assertFalse(cookie()->has('remember_token'));
    }

    public function testWebSignout(): void
    {
        $this->assertFalse($this->sessionAuth->check());

        $this->sessionAuth->signin('admin@qt.com', 'qwerty');

        $this->assertTrue($this->sessionAuth->check());

        $this->sessionAuth->signout();

        $this->assertFalse($this->sessionAuth->check());
    }

    public function testWebUser(): void
    {
        $this->sessionAuth->signin('admin@qt.com', 'qwerty');

        $this->assertInstanceOf(User::class, $this->sessionAuth->user());

        $this->assertEquals('admin@qt.com', $this->sessionAuth->user()->getFieldValue('email'));

        $this->assertEquals('admin', $this->sessionAuth->user()->getFieldValue('role'));
    }

    public function testWebCheck(): void
    {
        $this->assertFalse($this->sessionAuth->check());

        $this->sessionAuth->signin('admin@qt.com', 'qwerty');

        $this->assertTrue($this->sessionAuth->check());
    }

    public function testWebSignupAndSigninWithoutActivation(): void
    {

        $this->expectException(AuthException::class);

        $this->expectExceptionMessage(ExceptionMessages::INACTIVE_ACCOUNT);

        $this->sessionAuth->signup($this->guestUser);

        $this->assertTrue($this->sessionAuth->signin('guest@qt.com', '123456'));
    }

    public function testWebSignupAndActivateAccount(): void
    {
        $user = $this->sessionAuth->signup($this->guestUser);

        $this->sessionAuth->activate($user->getFieldValue('activation_token'));

        $this->assertTrue($this->sessionAuth->signin('guest@qt.com', '123456'));
    }

    public function testWebForgetReset(): void
    {
        $resetToken = $this->sessionAuth->forget('admin@qt.com');

        $this->sessionAuth->reset($resetToken, '123456789');

        $this->assertTrue($this->sessionAuth->signin('admin@qt.com', '123456789'));
    }

    public function testWebVerifyOtp(): void
    {
        config()->set('auth.two_fa', true);
        config()->set('auth.otp_expires', 2);
        $this->sessionAuth = $this->createSessionAuth();

        $otp_token = $this->sessionAuth->signin('admin@qt.com', 'qwerty');

        $this->assertTrue($this->sessionAuth->verifyOtp(123456789, $otp_token));
    }

    public function testWebSigninWithoutVerification(): void
    {
        config()->set('auth.two_fa', false);
        config()->set('auth.otp_expires', 2);
        $this->sessionAuth = $this->createSessionAuth();

        $this->assertTrue($this->sessionAuth->signin('admin@qt.com', 'qwerty'));
    }

    public function testWebSigninWithVerification(): void
    {
        config()->set('auth.two_fa', true);
        config()->set('auth.otp_expires', 2);
        $this->sessionAuth = $this->createSessionAuth();

        $this->assertIsString($this->sessionAuth->signin('admin@qt.com', 'qwerty'));
    }

    public function testWebResendOtp(): void
    {
        config()->set('auth.two_fa', true);
        config()->set('auth.otp_expires', 2);
        $this->sessionAuth = $this->createSessionAuth();

        $otp_token = $this->sessionAuth->signin('admin@qt.com', 'qwerty');

        $this->assertIsString($this->sessionAuth->resendOtp($otp_token));
    }

    public function testWebRefreshUser(): void
    {
        $this->sessionAuth->signin('admin@qt.com', 'qwerty');

        $user = $this->sessionAuth->user();

        $this->assertEquals('Admin', $user->firstname);

        $this->assertEquals('User', $user->lastname);

        $newUserData = [
            'firstname' => 'Super',
            'lastname' => 'Human',
        ];

        $this->authService->update('uuid', $user->uuid, $newUserData);

        $this->sessionAuth->refreshUser($user->uuid);

        $refreshedUser = $this->sessionAuth->user();

        $this->assertEquals('Super', $refreshedUser->firstname);

        $this->assertEquals('Human', $refreshedUser->lastname);
    }

    public function testWebRememberTokenLifetimeIsConfigurable(): void
    {
        config()->set('auth.session.remember_lifetime', 86400);
        $this->sessionAuth = $this->createSessionAuth();

        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);

        $this->assertTrue(cookie()->has('remember_token'));
    }

    public function testWebRememberTokenLifetimeAcceptsTheTextFromEnv(): void
    {
        config()->set('auth.session.remember_lifetime', '86400');
        $this->sessionAuth = $this->createSessionAuth();

        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);

        $this->assertTrue(cookie()->has('remember_token'));
    }
}
