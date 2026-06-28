<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth\Adapters;

use Quantum\Auth\Contracts\AuthenticatableInterface;
use Quantum\Auth\Contracts\AuthServiceInterface;
use Quantum\Auth\Exceptions\AuthException;
use Quantum\Jwt\Exceptions\JwtException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Auth\Traits\AuthTrait;
use Quantum\Auth\Enums\AuthKeys;
use Quantum\Mailer\Mailer;
use Quantum\Hasher\Hasher;
use Quantum\Jwt\JwtToken;
use ReflectionException;
use Quantum\Auth\User;
use Exception;

/**
 * Class ApiAuth
 * @package Quantum\Auth
 */
class JwtAuthAdapter implements AuthenticatableInterface
{
    use AuthTrait;

    protected JwtToken $jwt;

    /**
     * @param array<string, mixed> $config
     * @throws AuthException
     */
    public function __construct(AuthServiceInterface $authService, Mailer $mailer, Hasher $hasher, JwtToken $jwt, array $config = [])
    {
        $this->authService = $authService;
        $this->mailer = $mailer;
        $this->hasher = $hasher;
        $this->jwt = $jwt;
        $this->config = $config;

        $this->verifySchema($this->authService->userSchema());
    }

    /**
     * @inheritDoc
     * @return string|array<string, string>
     * @throws AuthException|DiException|JwtException|ReflectionException|Exception
     */
    public function signin(string $username, string $password): string|array
    {
        $user = $this->getUser($username, $password);

        if ($this->isTwoFactorEnabled()) {
            return $this->twoStepVerification($user);
        }

        return $this->setUpdatedTokens($user);
    }

    /**
     * @inheritDoc
     * @throws DiException|ReflectionException
     */
    public function signout(): bool
    {
        $refreshToken = request()->getHeader($this->keyFields[AuthKeys::REFRESH_TOKEN]);

        $user = $this->authService->get($this->keyFields[AuthKeys::REFRESH_TOKEN], $refreshToken);

        if ($user) {
            $this->authService->update(
                $this->keyFields[AuthKeys::REFRESH_TOKEN],
                $refreshToken,
                array_merge($this->getVisibleFields($user), [$this->keyFields[AuthKeys::REFRESH_TOKEN] => ''])
            );

            request()->deleteHeader($this->keyFields[AuthKeys::REFRESH_TOKEN]);
            request()->deleteHeader('Authorization');
            response()->delete('tokens');

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     * @throws JwtException|DiException|ReflectionException
     */
    public function user(): ?User
    {
        try {
            return $this->getUserFromAccessToken();
        } catch (Exception) {
            return $this->getUserFromRefreshToken();
        }
    }

    /**
     * Refresh user data
     * @throws JwtException|DiException|ReflectionException
     */
    public function refreshUser(string $uuid): bool
    {
        $user = $this->authService->get('uuid', $uuid);

        if (!$user) {
            return false;
        }

        $this->setUpdatedTokens($user);

        return true;
    }

    /**
     * Verify OTP
     * @return array<string, string>
     * @throws AuthException|JwtException|DiException|ReflectionException
     */
    public function verifyOtp(int $otp, string $otpToken): array
    {
        $user = $this->verifyAndUpdateOtp($otp, $otpToken);
        return $this->setUpdatedTokens($user);
    }

    /**
     * Get Updated Tokens
     * @return array<string, string>
     * @throws JwtException
     */
    protected function getUpdatedTokens(User $user): array
    {

        return [
            $this->keyFields[AuthKeys::REFRESH_TOKEN] => $this->generateToken(),
            $this->keyFields[AuthKeys::ACCESS_TOKEN] => base64_encode($this->jwt->setData($this->getVisibleFields($user))->compose()),
        ];
    }

    /**
     * Set Updated Tokens
     * @return array<string, string>
     * @throws JwtException|DiException|ReflectionException
     */
    protected function setUpdatedTokens(User $user): array
    {
        $tokens = $this->getUpdatedTokens($user);

        $this->authService->update(
            $this->keyFields[AuthKeys::USERNAME],
            $user->getFieldValue($this->keyFields[AuthKeys::USERNAME]),
            array_merge($this->getVisibleFields($user), [$this->keyFields[AuthKeys::REFRESH_TOKEN] => $tokens[$this->keyFields[AuthKeys::REFRESH_TOKEN]]])
        );

        request()->setHeader($this->keyFields[AuthKeys::REFRESH_TOKEN], $tokens[$this->keyFields[AuthKeys::REFRESH_TOKEN]]);
        request()->setHeader('Authorization', 'Bearer ' . $tokens[$this->keyFields[AuthKeys::ACCESS_TOKEN]]);
        response()->set('tokens', $tokens);

        return $tokens;
    }

    /**
     * Check Refresh Token
     */
    protected function checkRefreshToken(): ?User
    {
        return $this->authService->get(
            $this->keyFields[AuthKeys::REFRESH_TOKEN],
            request()->getHeader($this->keyFields[AuthKeys::REFRESH_TOKEN])
        );
    }

    /**
     * @throws DiException|ReflectionException
     */
    private function getUserFromAccessToken(): ?User
    {
        $authorizationBearer = request()->getAuthorizationBearer();

        if (!$authorizationBearer) {
            return null;
        }

        $accessToken = base64_decode($authorizationBearer);

        $userData = $this->jwt->retrieve($accessToken)->fetchData();

        return $userData ? (new User())->setData($userData) : null;
    }

    /**
     * @throws JwtException|DiException|ReflectionException
     */
    private function getUserFromRefreshToken(): ?User
    {
        if (!request()->hasHeader($this->keyFields[AuthKeys::REFRESH_TOKEN])) {
            return null;
        }

        $user = $this->checkRefreshToken();

        if ($user instanceof User) {
            $this->setUpdatedTokens($user);
            return $this->user();
        }

        return null;
    }
}
