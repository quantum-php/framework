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
 * class MailgunAdapter
 * @package Quantum\Mailer
 */
class MailgunAdapter implements MailerInterface
{
    use MailerTrait;

    public string $name = 'Mailgun';

    private string $apiKey;

    private string $apiUrl = 'https://api.mailgun.net/v3/';

    private HttpClient $httpClient;

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * MailgunAdapter constructor
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        $this->httpClient = new HttpClient();

        $this->apiKey = $params['api_key'];
        $this->apiUrl .= $params['domain'] . '/messages';
    }

    /**
     * Prepares the data
     */
    protected function prepare(): void
    {
        $this->data['from'] = $this->from['name'] . ' ' . $this->from['email'];

        $to = [];
        foreach ($this->addresses as $address) {
            $to[] = $address['email'];
        }

        $this->data['to'] = implode(',', $to);

        if ($this->subject) {
            $this->data['subject'] = $this->subject;
        }

        if ($this->message) {
            if ($this->templatePath) {
                $body = $this->createFromTemplate();
            } else {
                $body = is_array($this->message) ? implode('', $this->message) : $this->message;
            }

            $this->data['html'] = trim(str_replace("\n", '', $body));
        }
    }

    protected function sendEmail(): bool
    {
        try {
            $this->httpClient
                ->createRequest($this->apiUrl)
                ->setMethod('POST')
                ->setHeaders([
                    'Authorization' => 'Basic ' . base64_encode('api:' . $this->apiKey),
                    'Content-Type' => 'application/x-www-form-urlencoded',
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
