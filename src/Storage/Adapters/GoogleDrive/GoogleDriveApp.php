<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Adapters\GoogleDrive;

use Quantum\Storage\Contracts\TokenServiceInterface;
use Quantum\Encryption\Exceptions\CryptorException;
use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Storage\Contracts\CloudAppInterface;
use Quantum\Storage\Traits\CloudAppTrait;
use Quantum\App\Exceptions\BaseException;
use Quantum\HttpClient\HttpClient;
use Exception;

/**
 * Class GoogleDriveApp
 * @package Quantum\Storage
 */
class GoogleDriveApp implements CloudAppInterface
{
    use CloudAppTrait;

    /**
     * Authorization URL
     */
    public const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    /**
     * Authorization scope
     * @var string
     */
    public const AUTH_SCOPE = 'https://www.googleapis.com/auth/drive';

    /**
     * Token URL
     */
    public const AUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /**
     * URL for file metadata operations
     */
    public const FILE_METADATA_URL = 'https://www.googleapis.com/drive/v3/files';

    /**
     * URL for file media operations
     */
    public const FILE_MEDIA_URL = 'https://www.googleapis.com/upload/drive/v3/files';

    /**
     * Folder mimetype
     */
    public const FOLDER_MIMETYPE = 'application/vnd.google-apps.folder';

    /**
     * Kind/Type  of drive file
     */
    public const DRIVE_FILE_KIND = 'drive#file';

    /**
     * Error code for invalid token
     */
    public const INVALID_TOKEN_ERROR_CODE = 401;

    private HttpClient $httpClient;

    private string $appKey;

    private string $appSecret;

    private TokenServiceInterface $tokenService;

    /**
     * GoogleDriveApp constructor
     */
    public function __construct(string $appKey, string $appSecret, TokenServiceInterface $tokenService, HttpClient $httpClient)
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->tokenService = $tokenService;
        $this->httpClient = $httpClient;
    }

    /**
     * Gets Auth URL
     * @throws BaseException
     * @throws CryptorException
     * @throws DatabaseException
     */
    public function getAuthUrl(string $redirectUrl, string $accessType = 'offline'): string
    {
        $params = [
            'client_id' => $this->appKey,
            'response_type' => 'code',
            'state' => csrf_token(),
            'scope' => self::AUTH_SCOPE,
            'redirect_uri' => $redirectUrl,
            'access_type' => $accessType,
        ];

        return self::AUTH_URL . '?' . http_build_query($params, '', '&');
    }

    /**
     * Fetch tokens
     * @throws Exception
     */
    public function fetchTokens(string $code, string $redirectUrl = ''): ?object
    {
        $params = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->appKey,
            'client_secret' => $this->appSecret,
            'redirect_uri' => $redirectUrl,
        ];

        $response = $this->sendRequest(self::AUTH_TOKEN_URL, $params);

        $this->tokenService->saveTokens($response->access_token, $response->refresh_token);

        return $response;
    }

    /**
     * Fetches the access token by refresh token
     * @throws Exception
     */
    private function fetchAccessTokenByRefreshToken(string $refreshToken): string
    {
        $params = [
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
            'client_id' => $this->appKey,
            'client_secret' => $this->appSecret,
        ];

        $response = $this->sendRequest(self::AUTH_TOKEN_URL, $params);

        $this->tokenService->saveTokens($response->access_token);

        return $response->access_token;
    }

    /**
     * Sends rpc request
     * @param mixed $params
     * @return mixed|null
     * @throws Exception
     */
    public function rpcRequest(string $url, $params = [], string $method = 'POST', string $contentType = 'application/json')
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->tokenService->getAccessToken(),
                'Content-Type' => $contentType,
            ];
            return $this->sendRequest($url, $params, $headers, $method);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Gets file information
     * @param array<string, mixed> $params
     * @return mixed|null
     * @throws Exception
     */
    public function getFileInfo(string $fileId, bool $media = false, array $params = [])
    {
        $queryParam = $media ? '?alt=media' : '?fields=*';
        return $this->rpcRequest(GoogleDriveApp::FILE_METADATA_URL . '/' . $fileId . $queryParam, $params, 'GET');
    }

    /**
     * Checks if the access token need refresh
     */
    private function accessTokenNeedsRefresh(int $code, ?object $message = null): bool
    {
        return $code === self::INVALID_TOKEN_ERROR_CODE;
    }
}
