<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Mailer\Traits;

use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Di\Exceptions\DiException;
use Quantum\Debugger\Debugger;
use Quantum\Mailer\MailTrap;
use ReflectionException;
use Quantum\Di\Di;
use Exception;

/**
 * trait MailerTrait
 * @package Quantum\Mailer
 */
trait MailerTrait
{
    /**
     * From address and name
     * @var array<string, mixed>
     */
    private array $from = [];

    /**
     * To addresses
     * @var array<int|string, mixed>
     */
    private array $addresses = [];

    /**
     * Email subject
     */
    private ?string $subject = null;

    /**
     * Email body
     * @var array<string, mixed>|string|null
     */
    private $message;

    private static ?string $messageId = null;

    /**
     * Template path
     */
    private ?string $templatePath = null;

    /**
     * Reply To addresses
     * @var array<int|string, mixed>
     */
    protected array $replyToAddresses = [];

    /**
     * CC addresses
     * @var array<int|string, mixed>
     */
    protected array $ccAddresses = [];

    /**
     * BCC addresses
     * @var array<int|string, mixed>
     */
    protected array $bccAddresses = [];

    /**
     * Email attachments
     * @var array<int|string, mixed>
     */
    protected array $attachments = [];

    /**
     * Email attachments created from string
     * @var array<int|string, mixed>
     */
    protected array $stringAttachments = [];

    abstract protected function prepare(): void;

    abstract protected function sendEmail(): bool;

    /**
     * @return array<string>
     */
    abstract protected function getTransportErrors(): array;

    /**
     * @throws Exception
     */
    protected function resolveMessageId(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function getRenderedMessage(): ?string
    {
        return null;
    }

    protected function beforeSave(): void
    {
    }

    protected function resetTransportState(): void
    {
    }

    /**
     * Sets the 'From' email and the name
     */
    public function setFrom(string $email, ?string $name = null): MailerInterface
    {
        $this->from['email'] = $email;
        $this->from['name'] = $name;
        return $this;
    }

    /**
     * Gets 'From' email and the "name"
     * @return array<string, mixed>
     */
    public function getFrom(): array
    {
        return $this->from;
    }

    /**
     * Sets 'To' addresses
     */
    public function setAddress(string $email, ?string $name = null): MailerInterface
    {
        $this->addresses[] = [
            'email' => $email,
            'name' => $name,
        ];

        return $this;
    }

    /**
     * Gets 'To' addresses
     * @return array<int|string, mixed>
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }

    /**
     * Sets the subject
     */
    public function setSubject(?string $subject): MailerInterface
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Gets the subject
     * @return string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Sets the template
     */
    public function setTemplate(string $templatePath): MailerInterface
    {
        $this->templatePath = $templatePath;
        return $this;
    }

    /**
     * Gets the template
     */
    public function getTemplate(): ?string
    {
        return $this->templatePath;
    }

    /**
     * Sets the body
     * @param array<string, mixed>|string|null $message
     */
    public function setBody($message): MailerInterface
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Gets the body
     * @return array<string, mixed>|string|null
     */
    public function getBody()
    {
        return $this->message;
    }

    /**
     * @inheritDoc
     * @throws DiException
     * @throws ReflectionException
     * @throws Exception
     */
    public function send(): bool
    {
        $this->prepare();

        $sent = config()->get('mailer.mail_trap') ? $this->saveEmail() : $this->sendEmail();

        $this->resetFields();

        if (!$sent) {
            $errors = $this->getTransportErrors();
            if (!empty($errors)) {
                warning(implode(', ', $errors), ['tab' => Debugger::MAILS]);
            }
        }

        return $sent;
    }

    /**
     * Gets the message ID
     * @throws Exception
     */
    public function getMessageId(): ?string
    {
        if (self::$messageId) {
            return self::$messageId;
        }

        self::$messageId = $this->resolveMessageId();

        return self::$messageId;
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws Exception
     */
    private function saveEmail(): bool
    {
        $this->beforeSave();

        $messageId = $this->getMessageId();
        if ($messageId === null) {
            return false;
        }

        if (!Di::isRegistered(MailTrap::class)) {
            Di::register(MailTrap::class);
        }

        return Di::get(MailTrap::class)->saveMessage($messageId, $this->getMessageContent());
    }

    /**
     * Create message body from email template
     */
    private function createFromTemplate(): string
    {
        ob_start();
        ob_implicit_flush(false);

        if (is_array($this->message)) {
            extract($this->message);
        }

        require $this->templatePath . '.php';

        $content = ob_get_clean();
        return $content !== false ? $content : '';
    }

    /**
     * Gets the complete message
     * @throws Exception
     */
    private function getMessageContent(): string
    {
        return $this->getRenderedMessage() ?? $this->generateMessage();
    }

    /**
     * Generates the message content
     * @throws Exception
     */
    private function generateMessage(): string
    {
        $message = 'Date: ' . date('D, j M Y H:i:s O') . PHP_EOL;

        $message .= 'To: ';

        foreach ($this->addresses as $address) {
            $message .= $address['name'] . ' <' . $address['email'] . '>' . PHP_EOL;
        }

        $message .= 'From: ' . $this->from['name'] . ' <' . $this->from['email'] . '>' . PHP_EOL;

        $message .= 'Subject: ' . $this->subject . PHP_EOL;

        $message .= 'Message-ID: <' . $this->getMessageId() . '@' . base_url() . '>' . PHP_EOL;

        $message .= 'X-Mailer: ' . $this->name . PHP_EOL;

        $message .= 'MIME-Version: 1.0' . PHP_EOL;

        $message .= 'Content-Type: text/html; charset=UTF-8' . PHP_EOL . PHP_EOL;

        if ($this->templatePath) {
            $body = $this->createFromTemplate();
        } else {
            $body = is_string($this->message) ? $this->message : '';
        }

        return $message . ($body . PHP_EOL);
    }

    /**
     * Resets the fields
     */
    private function resetFields(): void
    {
        $this->from = [];
        $this->addresses = [];
        $this->subject = null;
        $this->message = null;
        $this->templatePath = null;

        $this->resetTransportState();
    }
}
