<?php

namespace Quantum\Tests\Unit\Storage\Adapters\GoogleDrive;

use Quantum\Storage\Adapters\GoogleDrive\GoogleDriveApp;
use Quantum\Tests\Unit\Storage\HttpClientTestCase;
use Quantum\Tests\Unit\AppTestCase;
use Exception;

class GoogleDriveAppTest extends AppTestCase
{
    use GoogleDriveTokenServiceTestCase;
    use HttpClientTestCase;

    private GoogleDriveApp $googleDriveApp;

    private string $appKey = 'x0hwm8wy63rrynm';

    private string $appSecret = 'xxx123yyy';

    private string $authCode = 'd4k29ovC7-UAAAAAAAA3Q45go2mLgjMhJSeJNBOo-EA';

    private string $redirectUrl = 'http://localhost/confirm';

    private array $tokensGrantResponse = [
        'access_token' => 'sl.BYEQ1_VadTz6nBU36WPBBVwokc3zWVMXGjcOKxV4Tadms8ZlEPM85aHVFa_k1sfjilCWOnl79RUncPZzJ3GhrqhLGIBFFRCH0rKMa_ZtcqkerJn-f5lu10Ki5PSw4fxYM80V4PL_',
        'refresh_token' => '-3S067m3M5kAAAAAAAAAAcQF8zVqFUuhK-PFkFqiOfFTgiazWj5NyU-1EGWIh0ZS',
    ];

    private array $fileMetadataResponse = [
        'id' => 'file1',
        'kind' => GoogleDriveApp::DRIVE_FILE_KIND,
        'name' => 'myFile',
        'mimeType' => 'text/plain',
    ];

    private string $fileContentResponse = 'Some plain text!';

    /**
     * @var string
     */
    private array $errorResponse = [
        'code' => GoogleDriveApp::INVALID_TOKEN_ERROR_CODE,
        'message' => 'Invalid access token',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $tokenServiceMock = $this->mockTokenService();

        $httpClientMock = $this->mockHttpClient();

        $this->googleDriveApp = new GoogleDriveApp($this->appKey, $this->appSecret, $tokenServiceMock, $httpClientMock);
    }

    public function testGoogleDriveGetAuthUrl(): void
    {
        $authUrl = $this->googleDriveApp->getAuthUrl($this->redirectUrl);

        $this->assertIsString($authUrl);

        $this->assertStringContainsString('client_id', $authUrl);

        $this->assertStringContainsString('access_type', $authUrl);
    }

    public function testGoogleDriveFetchTokens(): void
    {
        $this->currentResponse = (object) $this->tokensGrantResponse;

        $response = $this->googleDriveApp->fetchTokens($this->authCode, $this->redirectUrl);

        $this->assertIsObject($response);

        $this->assertTrue(property_exists($response, 'access_token'));

        $this->assertTrue(property_exists($response, 'refresh_token'));
    }

    public function testGoogleDriveRpcRequest(): void
    {
        $this->currentResponse = (object) $this->fileMetadataResponse;

        $response = $this->googleDriveApp->rpcRequest(GoogleDriveApp::FILE_METADATA_URL . '/' . $this->fileMetadataResponse['id']);

        $this->assertIsObject($response);

        $this->assertTrue(property_exists($response, 'id'));

        $this->assertTrue(property_exists($response, 'kind'));

        $this->assertTrue(property_exists($response, 'name'));

        $this->assertTrue(property_exists($response, 'mimeType'));
    }

    public function testGoogleDriveGetFileInfo(): void
    {
        $this->currentResponse = $this->fileContentResponse;

        $response = $this->googleDriveApp->getFileInfo($this->fileMetadataResponse['id'], true);

        $this->assertIsString($response);

        $this->assertEquals('Some plain text!', $response);
    }

    public function testGoogleDriveRequestWithAccessTokenExpired(): void
    {
        $this->currentErrors = ['code' => 401];

        $this->currentResponse = (object) $this->errorResponse;

        $response = $this->googleDriveApp->sendRequest(GoogleDriveApp::FILE_METADATA_URL . '/' . $this->fileMetadataResponse['id']);

        $this->assertIsObject($response);

        $this->assertEquals((object) $this->fileMetadataResponse, $response);
    }

    public function testGoogleDriveRequestWithATextBodyOnAnExpiredTokenRefreshesTheToken(): void
    {
        $this->currentErrors = ['code' => 401];

        $this->currentResponse = 'Unauthorized';

        $response = $this->googleDriveApp->sendRequest(GoogleDriveApp::FILE_METADATA_URL . '/' . $this->fileMetadataResponse['id']);

        $this->assertEquals((object) $this->fileMetadataResponse, $response);
    }

    public function testGoogleDriveRequestWithATextErrorThrowsTheText(): void
    {
        $this->currentErrors = ['code' => 500];

        $this->currentResponse = 'Internal Server Error';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Internal Server Error');

        $this->googleDriveApp->sendRequest(GoogleDriveApp::FILE_METADATA_URL . '/' . $this->fileMetadataResponse['id']);
    }
}
