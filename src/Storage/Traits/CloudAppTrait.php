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

        if ($errors) {
            $code = $errors['code'];

            if ($this->accessTokenNeedsRefresh($code, $responseBody)) {
                $prevUrl = $this->httpClient->url();

                if (empty($prevUrl)) {
                    throw new Exception('Cannot retry request: original URL is not available.', E_ERROR);
                }

                $prevData = $this->httpClient->getData();
                $prevHeaders = $this->httpClient->getRequestHeaders();

                $refreshToken = $this->tokenService->getRefreshToken();

                $accessToken = $this->fetchAccessTokenByRefreshToken($refreshToken);

                $prevHeaders['Authorization'] = 'Bearer ' . $accessToken;

                $responseBody = $this->sendRequest($prevUrl, $prevData, $prevHeaders);

            } else {
                throw new Exception(
                    json_encode($responseBody ?? $errors) ?: '',
                    E_ERROR
                );
            }
        }

        return $responseBody;
    }
}
