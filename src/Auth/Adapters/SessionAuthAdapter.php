<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth\Adapters;

use Quantum\Auth\Contracts\AuthenticatableInterface;
use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Auth\Contracts\AuthServiceInterface;
use Quantum\Session\Exceptions\SessionException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Auth\Exceptions\AuthException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Auth\Traits\AuthTrait;
use Quantum\Config\ConfigSection;
use Quantum\Auth\Enums\AuthKeys;
use Quantum\Hasher\Hasher;
use Quantum\Mailer\Mailer;
use ReflectionException;
use Quantum\Auth\User;
use Exception;

/**
 * Class WebAuth
 * @package Quantum\Auth
 */
class SessionAuthAdapter implements AuthenticatableInterface
{
    use AuthTrait;

    private const DEFAULT_REMEMBER_LIFETIME = 2592000;

    /**
     * Session key that remembers, until the OTP is verified, which user asked for "remember me"
     */
    private const AUTH_REMEMBER = 'auth_remember';

    private int $rememberLifetime;

    /**
     * @throws AuthException|ConfigException
     */
    public function __construct(AuthServiceInterface $authService, Mailer $mailer, Hasher $hasher, ConfigSection $config)
    {
        $this->authService = $authService;
        $this->mailer = $mailer;
        $this->hasher = $hasher;
        $this->readConfig($config);

        $this->verifySchema($this->authService->userSchema());
    }

    /**
     * Reads the auth settings once, so an invalid value fails when the adapter is built
     * @throws ConfigException
     */
    protected function readConfig(ConfigSection $config): void
    {
        $this->twoFactorEnabled = $config->getBool('two_fa', false);
        $this->otpExpires = $config->getInt('otp_expires', 2);
        $this->rememberLifetime = $config->getInt('session.remember_lifetime', self::DEFAULT_REMEMBER_LIFETIME);
    }

    /**
     * @inheritDoc
     * @throws AuthException
     * @throws ConfigException
     * @throws DatabaseException
     * @throws DiException
     * @throws ReflectionException
     * @throws SessionException
     * @throws Exception
     */
    public function signin(string $username, string $password, bool $remember = false): string|bool
    {
        $user = $this->getUser($username, $password);

        if ($this->isTwoFactorEnabled()) {
            if ($remember) {
                session()->set(self::AUTH_REMEMBER, $user->getFieldValue($this->keyFields[AuthKeys::USERNAME->value]));
            } else {
                session()->delete(self::AUTH_REMEMBER);
            }

            return $this->twoStepVerification($user);
        }

        if ($remember) {
            $this->setRememberToken($user);
        }

        session()->regenerateId();
        session()->set(self::AUTH_USER, $this->getVisibleFields($user));

        return true;
    }

    /**
     * @inheritDoc
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     * @throws BaseException
     */
    public function signout(): bool
    {
        session()->delete(self::AUTH_REMEMBER);

        if (session()->has(self::AUTH_USER)) {
            session()->delete(self::AUTH_USER);
            session()->regenerateId();
            $this->removeRememberToken();

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function user(): ?User
    {
        if (session()->has(self::AUTH_USER) && is_array(session()->get(self::AUTH_USER))) {
            return (new User())->setData(session()->get(self::AUTH_USER));
        }

        if (cookie()->has($this->keyFields[AuthKeys::REMEMBER_TOKEN->value])) {
            $user = $this->checkRememberToken();

            if ($user) {
                session()->set(self::AUTH_USER, $this->getVisibleFields($user));
                return $this->user();
            }
        }

        return null;
    }

    /**
     * Refresh user data
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function refreshUser(string $uuid): bool
    {
        $user = $this->authService->get('uuid', $uuid);

        if (!$user) {
            return false;
        }

        $sessionData = session()->get(self::AUTH_USER);
        $sessionData = array_merge($sessionData, $this->getVisibleFields($user));

        session()->set(self::AUTH_USER, $sessionData);

        return true;
    }

    /**
     * Verify OTP
     * @throws AuthException
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function verifyOtp(int $otp, string $otpToken): bool
    {
        $user = $this->verifyAndUpdateOtp($otp, $otpToken);

        $rememberFor = session()->get(self::AUTH_REMEMBER);
        session()->delete(self::AUTH_REMEMBER);

        if ($rememberFor !== null && $rememberFor === $user->getFieldValue($this->keyFields[AuthKeys::USERNAME->value])) {
            $this->setRememberToken($user);
        }

        session()->set(self::AUTH_USER, $this->getVisibleFields($user));

        return true;
    }

    /**
     * Check Remember Token
     * @return User|false
     * @throws BaseException
     */
    private function checkRememberToken(): false|\Quantum\Auth\User
    {
        $user = $this->authService->get(
            $this->keyFields[AuthKeys::REMEMBER_TOKEN->value],
            cookie()->get($this->keyFields[AuthKeys::REMEMBER_TOKEN->value])
        );

        if (!$user) {
            return false;
        }

        if ($this->isTwoFactorEnabled() && !empty($user->getFieldValue($this->keyFields[AuthKeys::OTP_TOKEN->value]))) {
            return false;
        }

        return $user;
    }

    /**
     * Set Remember Token
     * @throws BaseException
     */
    private function setRememberToken(User $user): void
    {
        $rememberToken = $this->generateToken();

        $this->authService->update(
            $this->keyFields[AuthKeys::USERNAME->value],
            $user->getFieldValue($this->keyFields[AuthKeys::USERNAME->value]),
            [$this->keyFields[AuthKeys::REMEMBER_TOKEN->value] => $rememberToken]
        );

        cookie()->set(
            $this->keyFields[AuthKeys::REMEMBER_TOKEN->value],
            $rememberToken,
            $this->rememberLifetime,
            '/',
            '',
            true,
            true
        );
    }

    /**
     * Remove Remember token
     */
    private function removeRememberToken(): void
    {
        if (cookie()->has($this->keyFields[AuthKeys::REMEMBER_TOKEN->value])) {
            $user = $this->authService->get(
                $this->keyFields[AuthKeys::REMEMBER_TOKEN->value],
                cookie()->get($this->keyFields[AuthKeys::REMEMBER_TOKEN->value])
            );

            if ($user) {
                $this->authService->update(
                    $this->keyFields[AuthKeys::REMEMBER_TOKEN->value],
                    $user->getFieldValue($this->keyFields[AuthKeys::REMEMBER_TOKEN->value]),
                    [$this->keyFields[AuthKeys::REMEMBER_TOKEN->value] => '']
                );
            }

            cookie()->delete($this->keyFields[AuthKeys::REMEMBER_TOKEN->value]);
        }
    }
}
