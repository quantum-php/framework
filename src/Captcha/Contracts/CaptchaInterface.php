<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Captcha\Contracts;

/**
 * Interface CaptchaInterface
 * @package Quantum\Captcha
 */
interface CaptchaInterface
{
    /**
     * Max time difference
     */
    public const MAX_TIME_DIFF = 60;

    /**
     * Captcha visible
     */
    public const CAPTCHA_VISIBLE = 'visible';

    /**
     * Captcha invisible
     */
    public const CAPTCHA_INVISIBLE = 'invisible';

    public function getName(): string;

    public function getType(): ?string;

    public function setType(string $type): self;

    /**
     * @return mixed
     */
    public function addToForm(string $formIdentifier);

    /**
     * @return mixed
     */
    public function verify(string $response);

    public function getErrorMessage(): ?string;
}
