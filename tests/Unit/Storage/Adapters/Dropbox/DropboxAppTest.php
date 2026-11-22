<?php

namespace Quantum\Tests\Unit\Storage\Adapters\Dropbox;

use Quantum\Tests\Unit\Storage\HttpClientTestCase;
use Quantum\Storage\Adapters\Dropbox\DropboxApp;
use PHPUnit\Framework\Attributes\DataProvider;
use Quantum\Tests\Unit\AppTestCase;
use Exception;

class DropboxAppTest extends AppTestCase
{
    use DropboxTokenServiceTestCase;
    use HttpClientTestCase;

    private DropboxApp $dropboxApp;

    private string $appKey = 'x0hwm8wy63rrynm';

    private string $appSecret = 'xxx123yyy';

    private string $authCode = 'd4k29ovC7-UAAAAAAAA3Q45go2mLgjMhJSeJNBOo-EA';

    private string $redirectUrl = 'http://localhost/confirm';

    private array $tokensGrantResponse = [
        'access_token' => 'sl.BYEQ1_VadTz6nBU36WPBBVwokc3zWVMXGjcOKxV4Tadms8ZlEPM85aHVFa_k1sfjilCWOnl79RUncPZzJ3GhrqhLGIBFFRCH0rKMa_ZtcqkerJn-f5lu10Ki5PSw4fxYM80V4PL_',
        'token_type' => 'bearer',
        'expires_in' => 14400,
        'refresh_token' => '-3S067m3M5kAAAAAAAAAAcQF8zVqFUuhK-PFkFqiOfFTgiazWj5NyU-1EGWIh0ZS',
        'scope' => 'account_info.read files.content.read files.content.write files.metadata.read files.metadata.write sharing.read sharing.write',
        'uid' => '64129261',
        'account_id' => 'dbid:AAC9tDKbzTQlyNms0ZcB_iH3wLv7yNn-iyE',
    ];

    private array $profileDataResponse = [
        'account_id' => 'dbid:AAH4f99T0taONIb-OurWxbNQ6ywGRopQngc',
        'disabled' => false,
        'email' => 'franz@dropbox.com',
        'email_verified' => true,
        'is_teammate' => false,
        'name' => [
            'abbreviated_name' => 'FF',
            'display_name' => 'Franz Ferdinand (Personal)',
            'familiar_name' => 'Franz',
            'given_name' => 'Franz',
            'surname' => 'Ferdinand',
        ],
        'profile_photo_url' => 'https://dl-web.dropbox.com/account_photo/get/69330102&size=128x128',
    ];

    private array $errorResponse = [
        'error' => [
            '.tag' => 'no_account',
        ],
        'error_summary' => 'no_account/...',
    ];

    private string $fileContentResponse = 'Some plain text!';

    public function setUp(): void
    {
        parent::setUp();

        $tokenServiceMock = $this->mockTokenService();

        $httpClientMock = $this->mockHttpClient();

        $this->dropboxApp = new DropboxApp($this->appKey, $this->appSecret, $tokenServiceMock, $httpClientMock);
    }

    public function testDropboxGetAuthUrl(): void
    {
        $authUrl = $this->dropboxApp->getAuthUrl($this->redirectUrl);

        $this->assertIsString($authUrl);

        $this->assertStringContainsString('client_id', $authUrl);

        $this->assertStringContainsString('token_access_type', $authUrl);
    }

    public function testDropboxFetchTokens(): void
    {
        $this->currentResponse = (object) $this->tokensGrantResponse;

        $response = $this->dropboxApp->fetchTokens($this->authCode, $this->redirectUrl);

        $this->assertIsObject($response);

        $this->assertTrue(property_exists($response, 'access_token'));

        $this->assertTrue(property_exists($response, 'refresh_token'));
    }

    public function testDropboxRpcRequest(): void
    {
        $this->currentResponse = (object) $this->profileDataResponse;

        $response = $this->dropboxApp->rpcRequest('/users/get_account');

        $this->assertIsObject($response);

        $this->assertTrue(property_exists($response, 'email'));

        $this->assertTrue(property_exists($response, 'name'));
    }

    public function testDropboxContentRequest(): void
    {
        $this->currentResponse = $this->fileContentResponse;

        $response = $this->dropboxApp->contentRequest('files/download', $this->dropboxApp->path('message.txt'));

        $this->assertIsString($response);

        $this->assertEquals('Some plain text!', $response);
    }

    public function testDropboxSendRequest(): void
    {
        $this->currentResponse = (object) $this->profileDataResponse;

        $response = $this->dropboxApp->sendRequest('https://api.dropboxapi.com/2/users/get_account');

        $this->assertIsObject($response);

        $this->assertEquals((object) $this->profileDataResponse, $response);
    }

    #[DataProvider('tokenErrorProvider')]
    public function testDropboxRequestWithAccessTokenExpired(string $tag): void
    {
        $this->currentErrors = ['code' => 401];

        $this->currentResponse = (object) ['error' => (object) ['.tag' => $tag], 'error_summary' => $tag . '/...'];

        $response = $this->dropboxApp->sendRequest('https://api.dropboxapi.com/2/users/get_account2');

        $this->assertIsObject($response);

        $this->assertEquals((object) $this->profileDataResponse, $response);
    }

    public static function tokenErrorProvider(): array
    {
        return [
            'expired access token' => ['expired_access_token'],
            'invalid access token' => ['invalid_access_token'],
        ];
    }

    public function testDropboxRequestWithAnUnauthorizedErrorWithoutBodyRefreshesTheToken(): void
    {
        $this->currentErrors = ['code' => 401];

        $this->currentResponse = null;

        $response = $this->dropboxApp->sendRequest('https://api.dropboxapi.com/2/users/get_account2');

        $this->assertEquals((object) $this->profileDataResponse, $response);
    }

    public function testDropboxRequestWithAnotherUnauthorizedErrorDoesNotRefreshTheToken(): void
    {
        $this->currentErrors = ['code' => 401];

        $this->currentResponse = (object) $this->errorResponse;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no_account');

        $this->dropboxApp->sendRequest('https://api.dropboxapi.com/2/users/get_account2');
    }

    public function testDropboxRequestWithATextErrorThrowsTheText(): void
    {
        $this->currentErrors = ['code' => 401];

        $this->currentResponse = 'Error in call to API function "users/get_account": Bad request';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error in call to API function');

        $this->dropboxApp->sendRequest('https://api.dropboxapi.com/2/users/get_account2');
    }

    public function testDropboxPathNormalizer(): void
    {
        $this->assertEquals(['path' => '/message.txt'], $this->dropboxApp->path('/message.txt'));

        $this->assertEquals(['path' => '/message.txt'], $this->dropboxApp->path('message.txt'));
    }

}
