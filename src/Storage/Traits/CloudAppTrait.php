<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Traits;

use Quantum\Http\Exceptions\HttpException;
use Quantum\App\Exceptions\BaseException;
use Exception;

/**
 * Trait CloudAppTrait
 * @package Quantum\Storage
 */
trait CloudAppTrait
{
    /**
     * Set while the access token is being refreshed: the token request and the retried
     * request may not start another refresh, so a request refreshes the token at most once
     */
    private bool $refreshingAccessToken = false;

    /**
     * @inheritDoc
     * @param array<string, mixed>|string|null $data
     * @param array<string, string> $headers
     * @throws HttpException|BaseException|Exception
     */
    public function sendRequest(string $uri, $data = null, array $headers = [], string $method = 'POST')
    {
        $this->httpClient
            ->createRequest($uri)
            ->setMethod($method)
            ->setData($data)
            ->setHeaders($headers)
            ->start();

        $errors = $this->httpClient->getErrors();
        $responseBody = $this->httpClient->getResponseBody();

        if (!$errors) {
            return $responseBody;
        }

        if (!$this->refreshingAccessToken && $this->accessTokenNeedsRefresh($errors['code'], $responseBody)) {
            $this->refreshingAccessToken = true;

            try {
                $accessToken = $this->fetchAccessTokenByRefreshToken($this->tokenService->getRefreshToken());

                $headers['Authorization'] = 'Bearer ' . $accessToken;

                return $this->sendRequest($uri, $data, $headers, $method);
            } finally {
                $this->refreshingAccessToken = false;
            }
        }

        throw new Exception($this->errorMessage($responseBody, $errors), E_ERROR);
    }

    /**
     * The message of a failed request: a text body as it is, the client's error
     * when there is no body, and any other body as JSON
     * @param array<int|string, mixed> $errors
     */
    private function errorMessage(mixed $responseBody, array $errors): string
    {
        if (is_string($responseBody) && $responseBody !== '') {
            return $responseBody;
        }

        if ($responseBody === null || $responseBody === '') {
            return json_encode($errors) ?: '';
        }

        return json_encode($responseBody) ?: '';
    }
}
