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
 * class SendinblueAdapter
 * @package Quantum\Mailer
 */
class SendinblueAdapter implements MailerInterface
{
    use MailerTrait;

    public string $name = 'Sendinblue';

    private string $apiKey;

    private string $apiUrl = 'https://api.sendinblue.com/v3/smtp/email';

    private HttpClient $httpClient;

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * SendinblueAdapter constructor
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
        $this->data['sender'] = $this->from;
        $this->data['to'] = $this->addresses;

        if ($this->subject) {
            $this->data['subject'] = $this->subject;
        }

        if ($this->message) {
            if ($this->templatePath) {
                $body = $this->createFromTemplate();
            } else {
                $body = is_array($this->message) ? implode('', $this->message) : $this->message;
            }

            $this->data['htmlContent'] = trim(str_replace("\n", '', $body));
        }
    }

    protected function sendEmail(): bool
    {
        try {
            $this->httpClient
                ->createRequest($this->apiUrl)
                ->setMethod('POST')
                ->setHeaders([
                    'Accept' => 'application/json',
                    'Content-type' => 'application/json',
                    'api-key' => $this->apiKey,
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
