<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Mailer\Enums;

/**
 * Class MailerType
 * @package Quantum\Mailer
 * @codeCoverageIgnore
 */
final class MailerType
{
    public const SMTP = 'smtp';

    public const MAILGUN = 'mailgun';

    public const MANDRILL = 'mandrill';

    public const SENDGRID = 'sendgrid';

    public const SENDINBLUE = 'sendinblue';

    public const RESEND = 'resend';

    private function __construct()
    {
    }
}
