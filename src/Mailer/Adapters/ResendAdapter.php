<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Mailer\Adapters;

use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Mailer\Traits\MailerTrait;
use Quantum\HttpClient\HttpClient;
use Exception;

/**
 * class ResendAdapter
 * @package Quantum\Mailer
 */
class ResendAdapter implements MailerInterface
{
    use MailerTrait;

    public string $name = 'Resend';

    private HttpClient $httpClient;

    private string $apiKey;

    private string $apiUrl = 'https://api.resend.com/emails';

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * ResendAdapter constructor
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        $this->httpClient = new HttpClient();
        $this->apiKey = $params['api_key'];
    }

    /**
     * Prepares the data
     */
    protected function prepare(): void
    {
        $fromName = $this->from['name'] ?? null;

        $this->data['from'] = $fromName
            ? $fromName . ' <' . $this->from['email'] . '>'
            : $this->from['email'];

        $this->data['to'] = array_column($this->addresses, 'email');

        if ($this->subject) {
            $this->data['subject'] = $this->subject;
        }

        if ($this->message) {
            if ($this->templatePath) {
                $body = $this->createFromTemplate();
            } else {
                $body = is_array($this->message) ? implode('', $this->message) : $this->message;
            }

            $this->data['html'] = trim(str_replace(["\n", "\r"], '', $body));
        }
    }

    protected function sendEmail(): bool
    {
        try {
            $this->httpClient
                ->createRequest($this->apiUrl)
                ->setMethod('POST')
                ->setHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->setData(json_encode($this->data))
                ->start();

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @return array<string>
     */
    protected function getTransportErrors(): array
    {
        return $this->httpClient->getErrors();
    }
}
