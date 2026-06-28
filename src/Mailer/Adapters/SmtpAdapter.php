<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Mailer\Adapters;

use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Di\Exceptions\DiException;
use Quantum\Mailer\Traits\MailerTrait;
use PHPMailer\PHPMailer\PHPMailer;
use Quantum\Debugger\Debugger;
use PHPMailer\PHPMailer\SMTP;
use ReflectionException;
use Exception;

/**
 * class SmtpAdapter
 * @package Quantum\Mailer
 */
class SmtpAdapter implements MailerInterface
{
    use MailerTrait;

    public string $name = 'SMTP';

    private PHPMailer $mailer;

    /**
     * SmtpAdapter constructor
     * @param array<string, mixed> $params
     * @throws DiException|ReflectionException
     */
    public function __construct(array $params)
    {
        $this->mailer = new PHPMailer(true);

        $this->mailer->CharSet = 'UTF-8';

        $this->setupSmtp($params);

        if (config()->get('debug')) {
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;

            $this->mailer->Debugoutput = function (string $message): void {
                warning($message, ['tab' => Debugger::MAILS]);
            };
        }
    }

    /**
     * Sets "Reply-To" address
     */
    public function setReplay(string $email, ?string $name = null): SmtpAdapter
    {
        $this->replyToAddresses[] = [
            'email' => $email,
            'name' => $name,
        ];

        return $this;
    }

    /**
     * Gets "Reply-To" addresses
     * @return array<int|string, mixed>
     */
    public function getReplays(): array
    {
        return $this->replyToAddresses;
    }

    /**
     * Sets "CC" address
     * @param string $email
     * @param string|null $name
     * @return SmtpAdapter
     */
    public function setCC(string $email, ?string $name = null): SmtpAdapter
    {
        $this->ccAddresses[] = [
            'email' => $email,
            'name' => $name,
        ];

        return $this;
    }

    /**
     * Gets "CC" addresses
     * @return array<int|string, mixed>
     */
    public function getCCs(): array
    {
        return $this->ccAddresses;
    }

    /**
     * Sets "BCC" address
     * @param string $email
     * @param string|null $name
     * @return SmtpAdapter
     */
    public function setBCC(string $email, ?string $name = null): SmtpAdapter
    {
        $this->bccAddresses[] = [
            'email' => $email,
            'name' => $name,
        ];

        return $this;
    }

    /**
     * Get "BCC" addresses
     * @return array<int|string, mixed>
     */
    public function getBCCs(): array
    {
        return $this->bccAddresses;
    }

    /**
     * Sets attachments from the path on the filesystem
     * @param string $attachment
     * @return SmtpAdapter
     */
    public function setAttachment(string $attachment): SmtpAdapter
    {
        $this->attachments[] = $attachment;
        return $this;
    }

    /**
     * Gets the attachments
     * @return array<int|string, mixed>
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * Sets attachment from the string
     * @param string $content
     * @param string $filename
     * @return SmtpAdapter
     */
    public function setStringAttachment(string $content, string $filename): SmtpAdapter
    {
        $this->stringAttachments[] = [
            'content' => $content,
            'filename' => $filename,
        ];

        return $this;
    }

    /**
     * Gets the string attachments
     * @return array<int|string, mixed>
     */
    public function getStringAttachments(): array
    {
        return $this->stringAttachments;
    }

    /**
     * @throws Exception
     */
    protected function resolveMessageId(): string
    {
        preg_match('/<(.*?)@/', $this->mailer->getLastMessageID(), $matches);
        return $matches[1] ?? bin2hex(random_bytes(16));
    }

    protected function getRenderedMessage(): ?string
    {
        return $this->mailer->getSentMIMEMessage();
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     */
    protected function beforeSave(): void
    {
        $this->mailer->preSend();
    }

    /**
     * @return array<string>
     */
    protected function getTransportErrors(): array
    {
        $error = $this->mailer->ErrorInfo;
        return $error !== '' ? [$error] : [];
    }

    protected function resetTransportState(): void
    {
        $this->replyToAddresses = [];
        $this->ccAddresses = [];
        $this->bccAddresses = [];
        $this->attachments = [];
        $this->stringAttachments = [];

        $this->mailer->clearAddresses();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearReplyTos();
        $this->mailer->clearAllRecipients();
        $this->mailer->clearAttachments();
        $this->mailer->clearCustomHeaders();
    }

    /**
     * Setups the SMTP
     * @param array<string, mixed> $params
     */
    private function setupSmtp(array $params): void
    {
        $this->mailer->isSMTP();
        $this->mailer->SMTPAuth = true;
        $this->mailer->Host = $params['host'];
        $this->mailer->SMTPSecure = $params['secure'];
        $this->mailer->Port = $params['port'];
        $this->mailer->Username = $params['username'];
        $this->mailer->Password = $params['password'];
    }

    /**
     * Prepares the data
     * @throws Exception
     */
    protected function prepare(): void
    {
        $this->mailer->setFrom($this->from['email'], $this->from['name']);

        if ($this->subject) {
            $this->mailer->Subject = $this->subject;
        }

        if ($this->message) {
            if ($this->templatePath) {
                $body = $this->createFromTemplate();
            } else {
                $body = is_array($this->message) ? implode('', $this->message) : $this->message;
            }

            $this->mailer->Body = trim(str_replace("\n", '', $body));
        }

        $this->fillProperties('addAddress', $this->addresses);
        $this->fillProperties('addReplyTo', $this->replyToAddresses);
        $this->fillProperties('addCC', $this->ccAddresses);
        $this->fillProperties('addBCC', $this->bccAddresses);
        $this->fillProperties('addAttachment', $this->attachments);
        $this->fillProperties('addStringAttachment', $this->stringAttachments);
    }

    /**
     * Fills the php mailer properties
     * @param array<string> $fields
     */
    private function fillProperties(string $method, array $fields = []): void
    {
        foreach ($fields as $field) {
            if (is_string($field)) {
                $this->mailer->$method($field);
            } else {
                $valOne = current($field);
                next($field);
                $valTwo = current($field);
                $this->mailer->$method($valOne, $valTwo);
                reset($field);
            }
        }
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     */
    protected function sendEmail(): bool
    {
        return $this->mailer->send();
    }
}
