<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Adapters\Dropbox;

use Quantum\Storage\Contracts\TokenServiceInterface;
use Quantum\Encryption\Exceptions\CryptorException;
use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Storage\Contracts\CloudAppInterface;
use Quantum\Lang\Exceptions\LangException;
use Quantum\Storage\Traits\CloudAppTrait;
use Quantum\Http\Exceptions\HttpException;
use Quantum\App\Exceptions\BaseException;
use Quantum\HttpClient\HttpClient;
use Exception;

/**
 * Class DropboxApp
 * @package Quantum\Storage
 */
class DropboxApp implements CloudAppInterface
{
    use CloudAppTrait;

    /**
     * Authorization URL
     */
    public const AUTH_URL = 'https://dropbox.com/oauth2/authorize';

    /**
     * Token URL
     */
    public const AUTH_TOKEN_URL = 'https://api.dropboxapi.com/oauth2/token';

    /**
     * URL for remote procedure call endpoints
     */
    public const RPC_API_URL = 'https://api.dropboxapi.com/2';

    /**
     * URL for content endpoints
     */
    public const CONTENT_API_URL = 'https://content.dropboxapi.com/2';

    /**
     * Create folder endpoint
     */
    public const ENDPOINT_CREATE_FOLDER = 'files/create_folder_v2';

    /**
     * Delete file endpoint
     */
    public const ENDPOINT_DELETE_FILE = 'files/delete_v2';

    /**
     * Download file endpoint
     */
    public const ENDPOINT_DOWNLOAD_FILE = 'files/download';

    /**
     * Upload file endpoint
     */
    public const ENDPOINT_UPLOAD_FILE = 'files/upload';

    /**
     * Move file endpoint
     */
    public const ENDPOINT_MOVE_FILE = 'files/move_v2';

    /**
     * Copy file endpoint
     */
    public const ENDPOINT_COPY_FILE = 'files/copy_v2';

    /**
     * Get metadata for file endpoint
     */
    public const ENDPOINT_FILE_METADATA = 'files/get_metadata';

    /**
     * List folder endpoint
     */
    public const ENDPOINT_LIST_FOLDER = 'files/list_folder';

    /**
     * Access token status indicating it needs refresh
     * @var array<string>
     */
    public const ACCESS_TOKEN_STATUS = ['invalid_access_token', 'expired_access_token'];

    /**
     * Error code for invalid token
     */
    public const INVALID_TOKEN_ERROR_CODE = 401;

    private HttpClient $httpClient;

    private string $appKey;

    private string $appSecret;

    private TokenServiceInterface $tokenService;

    /**
     * DropboxApp constructor
     */
    public function __construct(string $appKey, string $appSecret, TokenServiceInterface $tokenService, HttpClient $httpClient)
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->tokenService = $tokenService;
        $this->httpClient = $httpClient;
    }

    /**
     * Gets the auth URL
     * @throws BaseException
     * @throws CryptorException
     * @throws DatabaseException
     */
    public function getAuthUrl(string $redirectUrl, string $tokenAccessType = 'offline'): string
    {
        $params = [
            'client_id' => $this->appKey,
            'response_type' => 'code',
            'state' => csrf_token(),
            'redirect_uri' => $redirectUrl,
            'token_access_type' => $tokenAccessType,
        ];

        return self::AUTH_URL . '?' . http_build_query($params, '', '&');
    }

    /**
     * Fetch tokens
     * @throws BaseException
     * @throws HttpException
     * @throws LangException
     */
    public function fetchTokens(string $code, string $redirectUrl): ?object
    {
        $params = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->appKey,
            'client_secret' => $this->appSecret,
            'redirect_uri' => $redirectUrl,
        ];

        $tokenUrl = self::AUTH_TOKEN_URL . '?' . http_build_query($params, '', '&');

        $response = $this->sendRequest($tokenUrl);

        $this->tokenService->saveTokens($response->access_token, $response->refresh_token);

        return $response;
    }

    /**
     * Fetches the access token by refresh token
     * @throws BaseException
     * @throws HttpException
     * @throws LangException
     */
    private function fetchAccessTokenByRefreshToken(string $refreshToken): string
    {
        $params = [
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
            'client_id' => $this->appKey,
            'client_secret' => $this->appSecret,
        ];

        $tokenUrl = self::AUTH_TOKEN_URL . '?' . http_build_query($params, '', '&');

        $response = $this->sendRequest($tokenUrl);

        $this->tokenService->saveTokens($response->access_token);

        return $response->access_token;
    }

    /**
     * Sends rpc request
     * @param array<string, mixed>|null $params
     * @return mixed
     * @throws Exception
     */
    public function rpcRequest(string $endpoint, ?array $params = [])
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->tokenService->getAccessToken(),
            'Content-Type' => 'application/json',
        ];

        return $this->sendRequest(self::RPC_API_URL . '/' . $endpoint, $params, $headers);
    }

    /**
     * Sends content request
     * @param array<string, mixed> $params
     * @return mixed|null
     * @throws Exception
     */
    public function contentRequest(string $endpoint, array $params, string $content = '')
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->tokenService->getAccessToken(),
            'Dropbox-API-Arg' => json_encode($params) ?: '',
            'Content-Type' => 'application/octet-stream',
        ];

        return $this->sendRequest(self::CONTENT_API_URL . '/' . $endpoint, $content, $headers);
    }

    /**
     * Gets the normalized path
     * @return array{path: string}
     */
    public function path(string $name): array
    {
        return ['path' => '/' . trim($name, '/')];
    }

    /**
     * Checks if the access token need refresh
     */
    private function accessTokenNeedsRefresh(int $code, ?object $message = null): bool
    {
        if ($code !== self::INVALID_TOKEN_ERROR_CODE) {
            return false;
        }

        if (isset($message->error)) {
            $error = (array) $message->error;

            if (!isset($error['.tag']) && !in_array($error['.tag'], self::ACCESS_TOKEN_STATUS)) {
                return false;
            }
        }

        return true;
    }
}
