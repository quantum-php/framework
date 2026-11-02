<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth\Traits;

use Quantum\Auth\Contracts\AuthServiceInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Auth\Exceptions\AuthException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Jwt\Exceptions\JwtException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Auth\Enums\AuthKeys;
use Quantum\Mailer\Mailer;
use Quantum\Hasher\Hasher;
use ReflectionException;
use Quantum\Auth\User;
use DateInterval;
use Exception;
use DateTime;

/**
 * Trait AuthTrait
 * @package Quantum\Auth
 */
trait AuthTrait
{
    protected Mailer $mailer;

    protected Hasher $hasher;

    protected AuthServiceInterface $authService;

    protected int $otpLength = 6;

    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * @var array<string, string>
     */
    protected array $keyFields = [];

    /**
     * @var array<string>
     */
    protected array $visibleFields = [];

    /**
     * @inheritDoc
     * @throws ConfigException
     * @throws DiException
     * @throws JwtException
     * @throws ReflectionException
     * @throws BaseException
     */
    public function check(): bool
    {
        return !is_null($this->user());
    }

    /**
     * Sign Up
     * @param array<string, mixed> $userData
     * @param array<string, mixed>|null $customData
     */
    public function signup(array $userData, ?array $customData = null): User
    {
        $activationToken = $this->generateToken();

        $userData[$this->keyFields[AuthKeys::PASSWORD->value]] = $this->hasher->hash($userData[$this->keyFields[AuthKeys::PASSWORD->value]]);
        $userData[$this->keyFields[AuthKeys::ACTIVATION_TOKEN->value]] = $activationToken;

        $user = $this->authService->add($userData);

        $body = [
            'user' => $user,
            'activationToken' => $activationToken,
        ];

        if ($customData) {
            $body = array_merge($body, $customData);
        }

        $this->mailer->setSubject(t('common.activate_account'));
        $this->mailer->setTemplate(base_dir() . DS . 'shared' . DS . 'views' . DS . 'email' . DS . 'activate');

        $this->sendMail($user, $body);

        return $user;
    }

    /**
     * Activate
     */
    public function activate(string $token): void
    {
        $this->authService->update(
            $this->keyFields[AuthKeys::ACTIVATION_TOKEN->value],
            $token,
            [$this->keyFields[AuthKeys::ACTIVATION_TOKEN->value] => '']
        );
    }

    /**
     * Forget
     */
    public function forget(string $username): ?string
    {
        $user = $this->authService->get($this->keyFields[AuthKeys::USERNAME->value], $username);

        if (!$user) {
            return null;
        }

        $resetToken = $this->generateToken();

        $this->authService->update(
            $this->keyFields[AuthKeys::USERNAME->value],
            $username,
            [$this->keyFields[AuthKeys::RESET_TOKEN->value] => $resetToken]
        );

        $body = [
            'user' => $user,
            'resetToken' => $resetToken,
        ];

        $this->mailer->setSubject(t('common.reset_password'));
        $this->mailer->setTemplate(base_dir() . DS . 'shared' . DS . 'views' . DS . 'email' . DS . 'reset');

        $this->sendMail($user, $body);

        return $resetToken;
    }

    /**
     * Reset
     */
    public function reset(string $token, string $password): void
    {
        $user = $this->authService->get($this->keyFields[AuthKeys::RESET_TOKEN->value], $token);

        if ($user) {
            if (!$this->isActivated($user)) {
                $this->activate($token);
            }

            $this->authService->update(
                $this->keyFields[AuthKeys::RESET_TOKEN->value],
                $token,
                [
                    $this->keyFields[AuthKeys::PASSWORD->value] => $this->hasher->hash($password),
                    $this->keyFields[AuthKeys::RESET_TOKEN->value] => '',
                ]
            );
        }
    }

    /**
     * Resend OTP
     * @throws AuthException
     * @throws Exception
     */
    public function resendOtp(string $otpToken): string
    {
        $user = $this->authService->get($this->keyFields[AuthKeys::OTP_TOKEN->value], $otpToken);

        if (!$user) {
            throw AuthException::incorrectCredentials();
        }

        return $this->twoStepVerification($user);

    }

    /**
     * Gets the user by username and password
     * @throws AuthException
     */
    protected function getUser(string $username, string $password): User
    {
        $user = $this->authService->get($this->keyFields[AuthKeys::USERNAME->value], $username);

        if (!$user) {
            throw AuthException::incorrectCredentials();
        }

        if (!$this->hasher->check($password, $user->getFieldValue($this->keyFields[AuthKeys::PASSWORD->value]) ?? '')) {
            throw AuthException::incorrectCredentials();
        }

        if (!$this->isActivated($user)) {
            throw AuthException::inactiveAccount();
        }

        return $user;
    }

    /**
     * Two-Step Verification
     * @throws Exception
     */
    protected function twoStepVerification(User $user): string
    {
        $otp = random_number($this->otpLength);

        $otpToken = $this->generateToken($user->getFieldValue($this->keyFields[AuthKeys::USERNAME->value]));

        $time = new DateTime();

        $time->add(new DateInterval('PT' . ($this->config['otp_expires'] ?? 2) . 'M'));

        $this->authService->update(
            $this->keyFields[AuthKeys::USERNAME->value],
            $user->getFieldValue($this->keyFields[AuthKeys::USERNAME->value]),
            [
                $this->keyFields[AuthKeys::OTP->value] => $otp,
                $this->keyFields[AuthKeys::OTP_EXPIRY->value] => $time->format('Y-m-d H:i'),
                $this->keyFields[AuthKeys::OTP_TOKEN->value] => $otpToken,
            ]
        );

        $body = [
            'user' => $user,
            'code' => $otp,
        ];

        $this->mailer->setSubject(t('common.otp'));
        $this->mailer->setTemplate(base_dir() . DS . 'shared' . DS . 'views' . DS . 'email' . DS . 'verification');

        $this->sendMail($user, $body);

        return $otpToken;
    }

    /**
     * Verify and update OTP
     * @throws AuthException
     * @throws Exception
     */
    protected function verifyAndUpdateOtp(int $otp, string $otpToken): User
    {
        $user = $this->authService->get($this->keyFields[AuthKeys::OTP_TOKEN->value], $otpToken);

        if (!$user || $otp != $user->getFieldValue($this->keyFields[AuthKeys::OTP->value])) {
            throw AuthException::incorrectVerificationCode();
        }

        $otpExpiry = $user->getFieldValue($this->keyFields[AuthKeys::OTP_EXPIRY->value]);

        if (!$otpExpiry || new DateTime() >= new DateTime($otpExpiry)) {
            throw AuthException::verificationCodeExpired();
        }

        $this->authService->update(
            $this->keyFields[AuthKeys::USERNAME->value],
            $user->getFieldValue($this->keyFields[AuthKeys::USERNAME->value]),
            [
                $this->keyFields[AuthKeys::OTP->value] => null,
                $this->keyFields[AuthKeys::OTP_EXPIRY->value] => null,
                $this->keyFields[AuthKeys::OTP_TOKEN->value] => null,
            ]
        );

        return $user;
    }

    /**
     * Filters and gets visible fields
     * @return array<string, mixed>
     */
    protected function getVisibleFields(User $user): array
    {
        $userData = $user->getData();

        if (count($this->visibleFields) > 0) {
            foreach (array_keys($userData) as $field) {
                if (!in_array($field, $this->visibleFields)) {
                    unset($userData[$field]);
                }
            }
        }

        return $userData;
    }

    /**
     * Is user account activated
     */
    protected function isActivated(User $user): bool
    {
        return in_array($user->getFieldValue($this->keyFields[AuthKeys::ACTIVATION_TOKEN->value]), [null, '', '0'], true);
    }

    /**
     * Generate Token
     */
    protected function generateToken(?string $val = null): string
    {
        return base64_encode($this->hasher->hash($val ?: config()->get('app.key')) ?? '');
    }

    /**
     * Send email
     * @param array<string, mixed> $body
     */
    protected function sendMail(User $user, array $body): void
    {
        $fullName = ($user->hasField('firstname') && $user->hasField('lastname')) ? $user->getFieldValue('firstname') . ' ' . $user->getFieldValue('lastname') : '';

        $appEmail = config()->get('app.email') ?: '';
        $appName = config()->get('app.name') ?: '';

        $this->mailer->setFrom($appEmail, $appName)
            ->setAddress($user->getFieldValue($this->keyFields[AuthKeys::USERNAME->value]) ?? '', $fullName)
            ->setBody($body)
            ->send();
    }

    /**
     * Verify user schema
     * @param array<string, mixed> $schema
     * @throws AuthException
     */
    protected function verifySchema(array $schema): void
    {
        foreach (AuthKeys::cases() as $authKey) {
            if (!isset($schema[$authKey->value]) || !isset($schema[$authKey->value]['name'])) {
                throw AuthException::incorrectUserSchema();
            }

            $this->keyFields[$authKey->value] = $schema[$authKey->value]['name'];
        }

        foreach ($schema as $field) {
            if (isset($field['visible']) && $field['visible']) {
                $this->visibleFields[] = $field['name'];
            }
        }
    }

    protected function isTwoFactorEnabled(): bool
    {
        return filter_var($this->config['two_fa'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
