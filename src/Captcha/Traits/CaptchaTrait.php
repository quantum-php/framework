<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Captcha\Traits;

use Quantum\Captcha\Contracts\CaptchaInterface;
use Quantum\Asset\Exceptions\AssetException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\Http\Exceptions\HttpException;
use Quantum\HttpClient\HttpClient;
use Quantum\Asset\Asset;
use ErrorException;
use Exception;

/**
 * Trait CaptchaTrait
 * @package Quantum\Captcha
 */
trait CaptchaTrait
{
    protected ?string $siteKey;

    protected ?string $secretKey;

    protected HttpClient $http;

    /**
     * @var mixed|null
     */
    protected $type;

    /**
     * @var array<int|string, string>
     */
    protected array $errorCodes = [];

    /**
     * @var array<int, string>
     */
    protected array $elementAttributes = [];

    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @throws Exception
     */
    public function setType(string $type): CaptchaInterface
    {
        if (!$this->isValidCaptchaType($type)) {
            throw new Exception('Provided captcha type is not valid');
        }

        $this->type = $type;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Generates an HTML code block for the captcha
     * @throws AssetException
     * @throws Exception
     * @param array<string, mixed> $attributes
     */
    public function addToForm(string $formIdentifier = '', array $attributes = []): string
    {
        if (!$this->type) {
            throw new Exception('Captcha type is not set');
        }

        asset()->registerAsset(new Asset(Asset::JS, static::CLIENT_API, 'captcha', -1, ['async', 'defer']));

        if (strtolower($this->type) == self::CAPTCHA_INVISIBLE) {
            return $this->getInvisibleElement($formIdentifier);
        } else {
            return $this->getVisibleElement($attributes);
        }
    }

    /**
     * Checks the code given by the captcha
     * @throws LangException|HttpException|ErrorException|Exception
     */
    public function verify(string $code): bool
    {
        if (is_null($this->secretKey)) {
            throw new Exception('The secret key is not set');
        }

        if ($code === '' || $code === '0') {
            $this->errorCodes = ['internal-empty-response'];
            return false;
        }

        $query = [
            'secret' => $this->secretKey,
            'response' => $code,
            'remoteip' => get_user_ip(),
        ];

        $response = $this->http
            ->createRequest(static::VERIFY_URL . '?' . http_build_query($query))
            ->setMethod('GET')
            ->start()
            ->getResponseBody();

        if (empty($response)) {
            $this->errorCodes = ['internal-empty-response'];
            return false;
        }

        if (!is_object($response)) {
            $this->errorCodes = ['invalid-input-response'];
            return false;
        }

        if (isset($response->{'error-codes'}) && is_array($response->{'error-codes'})) {
            $this->errorCodes = $response->{'error-codes'};
        }

        if (isset($response->{'challenge_ts'}) && $this->detectReplayAttack($response->{'challenge_ts'})) {
            $this->errorCodes = ['replay-attack'];
            return false;
        }

        return isset($response->success) && $response->success;
    }

    public function getErrorMessage(): ?string
    {
        if (!empty($this->errorCodes)) {
            return current($this->errorCodes);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function getVisibleElement(array $attributes = []): string
    {
        $this->extractAttributes($attributes);

        return '<div class="' . implode(' ', $this->elementClasses) . '" data-sitekey="' . $this->siteKey . '" ' . implode(' ', $this->elementAttributes) . '></div>';
    }

    /**
     * @throws Exception
     */
    protected function getInvisibleElement(string $formIdentifier): string
    {
        if ($formIdentifier === '' || $formIdentifier === '0') {
            throw new Exception('Form identifier is not provided to captcha element');
        }

        return '<script>
                 document.addEventListener("DOMContentLoaded", function() {
                     const form = document.getElementById("' . $formIdentifier . '");
                     const submitButton = form.querySelector("button[type=submit]");
                     submitButton.setAttribute("data-sitekey", "' . $this->siteKey . '");
                     submitButton.setAttribute("data-callback", "onSubmit");
                     submitButton.classList.add("' . reset($this->elementClasses) . '");
                 })
                function onSubmit (){
                    document.getElementById("' . $formIdentifier . '").submit();
                }
            </script>';
    }

    protected function isValidCaptchaType(string $type): bool
    {
        $captchaTypes = [
            self::CAPTCHA_VISIBLE,
            self::CAPTCHA_INVISIBLE,
        ];

        return in_array($type, $captchaTypes, true);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return void
     */
    protected function extractAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if ($key == 'class') {
                $this->elementClasses[] = $value;
            } else {
                $this->elementAttributes[] = $key . '="' . $value . '"';
            }
        }
    }

    protected function detectReplayAttack(string $challengeTs): bool
    {
        return time() - strtotime($challengeTs) > self::MAX_TIME_DIFF;
    }
}
