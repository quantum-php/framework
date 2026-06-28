<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Mailer\Contracts;

/**
 * Interface MailerInterface
 * @package Quantum\Mailer
 */
interface MailerInterface
{
    /**
     * Sets the 'From' email and the name
     */
    public function setFrom(string $email, ?string $name = null): MailerInterface;

    /**
     * Gets the 'From' email and the name
     * @return array<string, mixed>
     */
    public function getFrom(): array;

    /**
     * Sets 'To' addresses
     */
    public function setAddress(string $email, ?string $name = null): MailerInterface;

    /**
     * Gets 'To' addresses
     * @return array<int|string, mixed>
     */
    public function getAddresses(): array;

    /**
     * Sets the subject
     */
    public function setSubject(?string $subject): MailerInterface;

    /**
     * Gets the subject
     */
    public function getSubject(): ?string;

    /**
     * Sets the template
     */
    public function setTemplate(string $templatePath): MailerInterface;

    /**
     * Gets the template
     */
    public function getTemplate(): ?string;

    /**
     * Sets the body
     * @param array<string, mixed>|string|null $message
     */
    public function setBody($message): MailerInterface;

    /**
     * Gets the body
     * @return array<string, mixed>|string|null
     */
    public function getBody();

    /**
     * Sends an email
     */
    public function send(): bool;
}
