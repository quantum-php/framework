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
     * @param array<string, mixed> $config
     * @throws AuthException
     */
    public function __construct(AuthServiceInterface $authService, Mailer $mailer, Hasher $hasher, array $config = [])
    {
        $this->authService = $authService;
        $this->mailer = $mailer;
        $this->hasher = $hasher;
        $this->config = $config;

        $this->verifySchema($this->authService->userSchema());
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

        if ($remember) {
            $this->setRememberToken($user);
        }

        if ($this->isTwoFactorEnabled()) {
            return $this->twoStepVerification($user);
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

        if (cookie()->has($this->keyFields[AuthKeys::REMEMBER_TOKEN])) {
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
            $this->keyFields[AuthKeys::REMEMBER_TOKEN],
            cookie()->get($this->keyFields[AuthKeys::REMEMBER_TOKEN])
        );

        if (!$user) {
            return false;
        }

        if ($this->isTwoFactorEnabled() && !empty($user->getFieldValue($this->keyFields[AuthKeys::OTP_TOKEN]))) {
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
            $this->keyFields[AuthKeys::USERNAME],
            $user->getFieldValue($this->keyFields[AuthKeys::USERNAME]),
            [$this->keyFields[AuthKeys::REMEMBER_TOKEN] => $rememberToken]
        );

        $rememberLifetime = $this->config['session']['remember_lifetime'] ?? self::DEFAULT_REMEMBER_LIFETIME;

        cookie()->set(
            $this->keyFields[AuthKeys::REMEMBER_TOKEN],
            $rememberToken,
            $rememberLifetime,
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
        if (cookie()->has($this->keyFields[AuthKeys::REMEMBER_TOKEN])) {
            $user = $this->authService->get(
                $this->keyFields[AuthKeys::REMEMBER_TOKEN],
                cookie()->get($this->keyFields[AuthKeys::REMEMBER_TOKEN])
            );

            if ($user) {
                $this->authService->update(
                    $this->keyFields[AuthKeys::REMEMBER_TOKEN],
                    $user->getFieldValue($this->keyFields[AuthKeys::REMEMBER_TOKEN]),
                    [$this->keyFields[AuthKeys::REMEMBER_TOKEN] => '']
                );
            }

            cookie()->delete($this->keyFields[AuthKeys::REMEMBER_TOKEN]);
        }
    }
}
