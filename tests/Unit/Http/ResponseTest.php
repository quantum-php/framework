<?php

namespace Quantum\Tests\Unit\Http;

use Quantum\Session\Factories\SessionFactory;
use Quantum\Http\Enums\ContentType;
use Quantum\Tests\Unit\AppTestCase;
use Throwable;

class ResponseTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        request()->create('GET', 'http://example.com');
    }

    public function tearDown(): void
    {
        response()->flush();

        server()->flush();

        parent::tearDown();
    }

    public function testResponseContentType(): void
    {
        $response = response();

        $this->assertEquals(ContentType::HTML, $response->getContentType());

        $response->setContentType(ContentType::JSON);

        $this->assertEquals(ContentType::JSON, $response->getContentType());
    }

    public function testResponseRedirect(): void
    {
        $response = response();

        $this->assertFalse($response->hasHeader('Location'));

        try {
            $response->redirect('/');
        } catch (Throwable $e) {

        }

        $this->assertTrue($response->hasHeader('Location'));

        $this->assertEquals('/', $response->getHeader('Location'));

        $this->assertEquals(302, $response->getStatusCode());

        try {
            $response->redirect('/home', 301);
        } catch (Throwable $e) {

        }

        $this->assertEquals('/home', $response->getHeader('Location'));

        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testResponseBackRedirectsToReferrer(): void
    {
        $referrer = base_url() . '/page?id=1#top';

        server()->set('HTTP_REFERER', $referrer);

        $response = response()->back();

        $this->assertEquals($referrer, $response->getHeader('Location'));

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testResponseBackFallsBackToBaseUrlWithExternalReferrer(): void
    {
        server()->set('HTTP_REFERER', 'https://attacker.example/page');

        $response = response()->back();

        $this->assertEquals(base_url(), $response->getHeader('Location'));
    }

    public function testResponseBackFallsBackToBaseUrlWithOtherPortOrScheme(): void
    {
        $base = parse_url(base_url());

        $otherScheme = $base['scheme'] === 'https' ? 'http' : 'https';

        foreach ([
            $base['scheme'] . '://' . $base['host'] . ':9999/page',
            $otherScheme . '://' . $base['host'] . '/page',
        ] as $referrer) {
            server()->set('HTTP_REFERER', $referrer);

            $response = response()->back();

            $this->assertEquals(base_url(), $response->getHeader('Location'), $referrer);
        }
    }

    public function testResponseBackAcceptsSameOriginReferrerForms(): void
    {
        $base = parse_url(base_url());

        $defaultPort = $base['scheme'] === 'https' ? 443 : 80;

        foreach ([
            strtoupper($base['scheme']) . '://' . strtoupper($base['host']) . '/page',
            $base['scheme'] . '://' . $base['host'] . ':' . $defaultPort . '/page',
            $base['scheme'] . '://' . $base['host'],
        ] as $referrer) {
            server()->set('HTTP_REFERER', $referrer);

            $response = response()->back();

            $this->assertEquals($referrer, $response->getHeader('Location'), $referrer);
        }
    }

    public function testResponseBackWithHttpsBaseUrl(): void
    {
        $baseUrl = config()->get('app.base_url');

        config()->set('app.base_url', 'https://example.com');

        foreach (['https://example.com/page', 'https://example.com:443/page'] as $referrer) {
            server()->set('HTTP_REFERER', $referrer);

            $response = response()->back();

            $this->assertEquals($referrer, $response->getHeader('Location'), $referrer);
        }

        server()->set('HTTP_REFERER', 'http://example.com/page');

        $response = response()->back();

        $this->assertEquals('https://example.com', $response->getHeader('Location'));

        config()->set('app.base_url', $baseUrl);
    }

    public function testResponseBackFallsBackToBaseUrlWithMalformedReferrer(): void
    {
        foreach ([
            'not a url',
            '/page',
            '//attacker.example/page',
            'https:///page',
            'javascript:alert(1)',
            base_url() . '\\@attacker.example',
            base_url() . '@attacker.example',
        ] as $referrer) {
            server()->set('HTTP_REFERER', $referrer);

            $response = response()->back();

            $this->assertEquals(base_url(), $response->getHeader('Location'), $referrer);
        }
    }

    public function testResponseBackFallsBackToBaseUrl(): void
    {
        $response = response()->back();

        $this->assertEquals(base_url(), $response->getHeader('Location'));

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testResponseBackFallsBackToBaseUrlWithEmptyReferrer(): void
    {
        server()->set('HTTP_REFERER', '');

        $response = response()->back();

        $this->assertEquals(base_url(), $response->getHeader('Location'));
    }

    public function testResponseBackWithStatusCode(): void
    {
        $response = response()->back([], 301);

        $this->assertEquals(base_url(), $response->getHeader('Location'));

        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testResponseBackWithOldData(): void
    {
        $session = SessionFactory::get();

        $referrer = base_url() . '/signup';

        server()->set('HTTP_REFERER', $referrer);

        $response = response()->back(['firstname' => 'Josh', 'lastname' => 'Doe']);

        $this->assertEquals($referrer, $response->getHeader('Location'));

        $this->assertEquals('Josh', old('firstname'));

        $this->assertEquals('Doe', old('lastname'));

        $session->flush();
    }

    public function testResponseBackWithoutDataKeepsOldData(): void
    {
        $session = SessionFactory::get();

        response()->back(['firstname' => 'Josh']);

        response()->back();

        $this->assertEquals('Josh', old('firstname'));

        $session->flush();
    }

}
