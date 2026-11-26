<?php

namespace Quantum\Tests\Unit\Auth\Adapters;

use Quantum\Auth\Contracts\AuthServiceInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Auth\Adapters\SessionAuthAdapter;
use Quantum\Auth\Exceptions\AuthException;
use Quantum\Auth\Enums\ExceptionMessages;
use Quantum\Tests\Unit\Auth\AuthTestCase;
use Quantum\Auth\Adapters\JwtAuthAdapter;
use Quantum\Auth\Traits\AuthTrait;
use Quantum\Mailer\Mailer;
use Quantum\Hasher\Hasher;
use ArgumentCountError;
use Quantum\Auth\User;
use ReflectionMethod;
use RuntimeException;
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
            config()->section('auth')
        );
    }

    private function enableTwoFactor(): void
    {
        config()->set('auth.two_fa', true);
        config()->set('auth.otp_expires', 2);
        $this->sessionAuth = $this->createSessionAuth();
    }

    private function createSessionAuthWith(AuthServiceInterface $authService, Mailer $mailer): SessionAuthAdapter
    {
        return new SessionAuthAdapter($authService, $mailer, (new Hasher())->setCost(4), config()->section('auth'));
    }

    /**
     * An auth service that throws when an update sets the given field to a non-empty value
     */
    private function authServiceFailingOn(string $field): AuthServiceInterface
    {
        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('userSchema')->andReturn($this->userSchema);
        $authService->shouldReceive('get')->andReturnUsing(fn (...$args) => $this->authService->get(...$args));
        $authService->shouldReceive('update')->andReturnUsing(function (string $key, string $value, array $data) use ($field) {
            if (!empty($data[$field])) {
                throw new RuntimeException('The database is down');
            }

            return $this->authService->update($key, $value, $data);
        });

        return $authService;
    }

    /**
     * A mailer whose send() throws while $fails is true
     */
    private function mailerFailingWhen(bool &$fails): Mailer
    {
        $mailer = Mockery::mock(Mailer::class);

        foreach (['setFrom', 'setAddress', 'setSubject', 'setBody', 'setTemplate'] as $method) {
            $mailer->shouldReceive($method)->andReturn($mailer);
        }

        $mailer->shouldReceive('send')->andReturnUsing(function () use (&$fails) {
            if ($fails) {
                throw new RuntimeException('The mail server is down');
            }

            return true;
        });

        return $mailer;
    }

    private function storedRememberToken(): string
    {
        return (string) $this->authService->get('email', 'admin@qt.com')->getFieldValue('remember_token');
    }

    public function tearDown(): void
    {
        self::$users = [];

        $this->sessionAuth->signout();

        parent::tearDown();
    }

    public function testWebAdapterConstructor(): void
    {
        $this->assertInstanceOf(SessionAuthAdapter::class, $this->sessionAuth);
    }

    public function testWebAdapterRequiresItsConfig(): void
    {
        $this->expectException(ArgumentCountError::class);

        new SessionAuthAdapter($this->authService, $this->mailer, (new Hasher())->setCost(4));
    }

    public function testEveryAuthAdapterReadsItsOwnConfig(): void
    {
        $this->assertTrue((new ReflectionMethod(AuthTrait::class, 'readConfig'))->isAbstract());

        foreach ([SessionAuthAdapter::class, JwtAuthAdapter::class] as $adapter) {
            $this->assertSame($adapter, (new ReflectionMethod($adapter, 'readConfig'))->getDeclaringClass()->getName());
        }
    }

    public function testWebAdapterRejectsAUserSchemaWithAMissingKey(): void
    {
        $schema = $this->userSchema;
        unset($schema['otpToken']);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('userSchema')->andReturn($schema);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage(ExceptionMessages::INCORRECT_USER_SCHEMA);

        new SessionAuthAdapter($authService, $this->mailer, (new Hasher())->setCost(4), config()->section('auth'));
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

    public function testWebSigninWithRememberAndVerificationSetsNoRememberCookieBeforeTheOtp(): void
    {
        $this->enableTwoFactor();

        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);

        $this->assertFalse(cookie()->has('remember_token'));
        $this->assertSame('', $this->storedRememberToken());
    }

    public function testWebRememberCookieOfAnUnverifiedSigninDoesNotSignInAfterTheOwnerVerifiesElsewhere(): void
    {
        $this->enableTwoFactor();

        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);
        $firstBrowserCookie = cookie()->has('remember_token') ? cookie()->get('remember_token') : null;

        cookie()->delete('remember_token');
        session()->flush();

        $otpToken = $this->sessionAuth->signin('admin@qt.com', 'qwerty');
        $this->sessionAuth->verifyOtp(123456789, $otpToken);

        session()->flush();
        cookie()->delete('remember_token');

        if ($firstBrowserCookie !== null) {
            cookie()->set('remember_token', $firstBrowserCookie);
        }

        $this->assertNull($this->sessionAuth->user());
        $this->assertSame('', $this->storedRememberToken());
    }

    public function testWebSigninWithRememberLeavesNoRememberTokenWhenTheOtpCannotBeStored(): void
    {
        $this->enableTwoFactor();

        $sessionAuth = $this->createSessionAuthWith($this->authServiceFailingOn('otp'), $this->mailer);

        try {
            $sessionAuth->signin('admin@qt.com', 'qwerty', true);
            $this->fail('The OTP setup did not fail');
        } catch (RuntimeException $e) {
            $this->assertSame('The database is down', $e->getMessage());
        }

        $this->assertFalse(cookie()->has('remember_token'));
        $this->assertSame('', $this->storedRememberToken());
    }

    public function testWebVerifyOtpSetsTheRememberCookieWhenRememberWasAsked(): void
    {
        $this->enableTwoFactor();

        $otpToken = $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);
        $this->sessionAuth->verifyOtp(123456789, $otpToken);

        $this->assertTrue(cookie()->has('remember_token'));
        $this->assertSame(cookie()->get('remember_token'), $this->storedRememberToken());

        session()->delete('auth_user');

        $this->assertTrue($this->sessionAuth->check());
    }

    public function testWebVerifyOtpAfterResendSetsTheRememberCookieWhenRememberWasAsked(): void
    {
        $this->enableTwoFactor();

        $otpToken = $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);
        $otpToken = $this->sessionAuth->resendOtp($otpToken);
        $this->sessionAuth->verifyOtp(123456789, $otpToken);

        $this->assertTrue(cookie()->has('remember_token'));
        $this->assertSame(cookie()->get('remember_token'), $this->storedRememberToken());
    }

    public function testWebSigninWithoutRememberForgetsAnEarlierRememberRequestWhenTheOtpMailFails(): void
    {
        $this->enableTwoFactor();

        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);

        $mailFails = true;
        $sessionAuth = $this->createSessionAuthWith($this->authService, $this->mailerFailingWhen($mailFails));

        try {
            $sessionAuth->signin('admin@qt.com', 'qwerty');
            $this->fail('The OTP mail did not fail');
        } catch (RuntimeException $e) {
            $this->assertSame('The mail server is down', $e->getMessage());
        }

        $mailFails = false;
        $otpToken = $sessionAuth->resendOtp($this->authService->get('email', 'admin@qt.com')->getFieldValue('otp_token'));
        $sessionAuth->verifyOtp(123456789, $otpToken);

        $this->assertFalse(cookie()->has('remember_token'));
        $this->assertSame('', $this->storedRememberToken());
    }

    public function testWebSignoutForgetsARememberRequestWaitingForTheOtp(): void
    {
        $this->enableTwoFactor();

        $otpToken = $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);
        $this->sessionAuth->signout();
        $this->sessionAuth->verifyOtp(123456789, $otpToken);

        $this->assertFalse(cookie()->has('remember_token'));
        $this->assertSame('', $this->storedRememberToken());
    }

    public function testWebVerifyOtpForgetsTheRememberRequestWhenTheRememberTokenCannotBeStored(): void
    {
        $this->enableTwoFactor();

        $sessionAuth = $this->createSessionAuthWith($this->authServiceFailingOn('remember_token'), $this->mailer);
        $otpToken = $sessionAuth->signin('admin@qt.com', 'qwerty', true);

        try {
            $sessionAuth->verifyOtp(123456789, $otpToken);
            $this->fail('Storing the remember token did not fail');
        } catch (RuntimeException $e) {
            $this->assertSame('The database is down', $e->getMessage());
        }

        $this->assertFalse(session()->has('auth_remember'));
        $this->assertNull($sessionAuth->user());
    }

    public function testWebVerifyOtpSetsNoRememberCookieWhenRememberWasNotAsked(): void
    {
        $this->enableTwoFactor();

        $otpToken = $this->sessionAuth->signin('admin@qt.com', 'qwerty');
        $this->sessionAuth->verifyOtp(123456789, $otpToken);

        $this->assertFalse(cookie()->has('remember_token'));
        $this->assertSame('', $this->storedRememberToken());
    }

    public function testWebSigninWithoutRememberForgetsAnEarlierRememberRequest(): void
    {
        $this->enableTwoFactor();

        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);
        $otpToken = $this->sessionAuth->signin('admin@qt.com', 'qwerty');
        $this->sessionAuth->verifyOtp(123456789, $otpToken);

        $this->assertFalse(cookie()->has('remember_token'));
        $this->assertSame('', $this->storedRememberToken());
    }

    public function testWebVerifyOtpGivesTheSessionANewId(): void
    {
        $this->enableTwoFactor();

        $otpToken = $this->sessionAuth->signin('admin@qt.com', 'qwerty');

        session()->set('cart', 'kept');
        $sessionIdBeforeOtp = session()->getId();

        $this->sessionAuth->verifyOtp(123456789, $otpToken);

        $this->assertNotSame($sessionIdBeforeOtp, session()->getId());
        $this->assertTrue($this->sessionAuth->check());
        $this->assertSame('kept', session()->get('cart'));
    }

    public function testWebSigninThroughTheRememberCookieGivesTheSessionANewId(): void
    {
        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);

        session()->delete('auth_user');
        session()->set('cart', 'kept');
        $sessionIdBeforeCookie = session()->getId();

        $this->assertNotNull($this->sessionAuth->user());
        $this->assertNotSame($sessionIdBeforeCookie, session()->getId());
        $this->assertSame('kept', session()->get('cart'));

        $sessionIdAfterCookie = session()->getId();
        $this->sessionAuth->user();

        $this->assertSame($sessionIdAfterCookie, session()->getId());
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

    public function testWebAdapterRejectsARememberLifetimeThatIsNotANumberBeforeTouchingTheToken(): void
    {
        $this->sessionAuth->signin('admin@qt.com', 'qwerty', true);
        $token = $this->authService->get('email', 'admin@qt.com')->getFieldValue('remember_token');

        config()->set('auth.session.remember_lifetime', 'abc');

        try {
            $this->createSessionAuth();
            $this->fail('The adapter was built with an invalid remember_lifetime');
        } catch (ConfigException $e) {
            $this->assertSame('The config key `session.remember_lifetime` has the value `abc`, which is not a whole number.', $e->getMessage());
        }

        $this->assertNotEmpty($token);
        $this->assertSame($token, $this->authService->get('email', 'admin@qt.com')->getFieldValue('remember_token'));
    }

    public function testWebSigninWithVerificationReadsTheSettingsGivenAsText(): void
    {
        config()->set('auth.two_fa', 'yes');
        config()->set('auth.otp_expires', '5');
        $this->sessionAuth = $this->createSessionAuth();

        $this->assertIsString($this->sessionAuth->signin('admin@qt.com', 'qwerty'));
    }

    public function testWebSigninWithVerificationUsesTheDefaultOtpExpiryWhenItIsNotSet(): void
    {
        config()->set('auth.two_fa', true);
        config()->delete('auth.otp_expires');
        $this->sessionAuth = $this->createSessionAuth();

        $this->assertIsString($this->sessionAuth->signin('admin@qt.com', 'qwerty'));
    }

    public function testWebAdapterRejectsAnOtpExpiryThatIsNotANumberEvenWithTwoFactorOff(): void
    {
        config()->set('auth.two_fa', false);
        config()->set('auth.otp_expires', 'abc');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The config key `otp_expires` has the value `abc`, which is not a whole number.');

        $this->createSessionAuth();
    }
}
