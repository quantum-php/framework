<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Mailer;

use Quantum\Storage\Exceptions\FileSystemException;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Phemail\Message\MessagePartInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Phemail\Message\MessagePart;
use Quantum\Storage\FileSystem;
use Phemail\MessageParser;
use ReflectionException;

/**
 * class MailTrap
 * @package Quantum\Mailer
 */
class MailTrap
{
    private FileSystem $fs;

    private MessageParser $parser;

    /**
     * @var MessagePart|MessagePartInterface
     */
    private $message;

    private string $emailsDirectory;

    /**
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->fs = FileSystemFactory::get();
        $this->parser = new MessageParser();
        $this->emailsDirectory = base_dir() . DS . 'shared' . DS . 'emails';
    }

    /**
     * Saves the message on the local file
     */
    public function saveMessage(string $filename, string $content): bool
    {
        if (!$this->fs->isDirectory($this->emailsDirectory)) {
            return false;
        }

        return (bool) $this->fs->put($this->emailsDirectory . DS . $filename . '.eml', $content);
    }

    /**
     * Gets the parsed email
     * @throws BaseException
     */
    public function parseMessage(string $filename): MailTrap
    {
        $filePath = $this->emailsDirectory . DS . $filename . '.eml';

        if (!$this->fs->exists($filePath)) {
            throw FileSystemException::fileNotFound($filename);
        }

        $this->message = $this->parser->parse($filePath);
        return $this;
    }

    /**
     * Gets the parsed message ID
     */
    public function getParsedMessageId(): ?string
    {
        return $this->message->getHeaderValue('message-id');
    }

    /**
     * Gets the parsed X Mailer
     */
    public function getParsedXMailer(): ?string
    {
        return $this->message->getHeaderValue('x-mailer');
    }

    /**
     * Gets the parsed mime version
     */
    public function getParsedMimeVersion(): ?string
    {
        return $this->message->getHeaderValue('mime-version');
    }

    /**
     * Gets the parsed content type of the email
     */
    public function getParsedContentType(): ?string
    {
        return $this->message->getHeaderValue('content-type');
    }

    /**
     * Gets the parsed date
     */
    public function getParsedDate(): ?string
    {
        return $this->message->getHeaderValue('date');
    }

    /**
     * Gets the parsed 'To' addresses
     * @return array<string>
     */
    public function getParsedToAddresses(): array
    {
        $addresses = explode(',', $this->message->getHeaderValue('to'));
        return array_map('trim', $addresses);
    }

    /**
     * Gets the parsed 'From' address
     */
    public function getParsedFromAddress(): ?string
    {
        return $this->message->getHeaderValue('from');
    }

    /**
     * Gets the parsed 'CC' addresses
     * @return array<string>
     */
    public function getParsedCcAddresses(): array
    {
        $addresses = explode(',', $this->message->getHeaderValue('cc'));
        return array_map('trim', $addresses);
    }

    /**
     * Gets the parsed 'BCC' addresses
     * @return array<string>
     */
    public function getParsedBccAddresses(): array
    {
        $addresses = explode(',', $this->message->getHeaderValue('bcc'));
        return array_map('trim', $addresses);
    }

    /**
     * Gets the 'Reply To' addresses
     * @return array<string>
     */
    public function getParsedReplyToAddresses(): array
    {
        $addresses = explode(',', $this->message->getHeaderValue('reply-to'));
        return array_map('trim', $addresses);
    }

    /**
     * Gets the parsed subject
     */
    public function getParsedSubject(): ?string
    {
        return $this->message->getHeaderValue('subject');
    }

    /**
     * Gets the parsed body
     */
    public function getParsedBody(): string
    {
        $body = '';

        if ($this->message->isMultiPart()) {
            $parts = $this->message->getParts();

            if (isset($parts[0]) && $parts[0]->isText()) {
                $body = $parts[0]->getContents();
            }
        } else {
            $body = $this->message->getContents();
        }

        return $body;
    }

    /**
     * Gets the parsed attachments
     * @return array<int, array<string, mixed>>|null
     */
    public function getParsedAttachments(): ?array
    {
        $parsedAttachments = $this->message->getAttachments();

        if (empty($parsedAttachments)) {
            return null;
        }

        $attachments = [];

        foreach ($parsedAttachments as $parsedAttachment) {
            $attachments[] = [
                'filename' => $parsedAttachment->getHeaderAttribute('content-disposition', 'filename'),
                'content-type' => $parsedAttachment->getHeaderValue('content-type'),
                'content' => $parsedAttachment->getContents(),
            ];
        }

        return $attachments;
    }
}
