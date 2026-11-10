<?php

namespace Quantum\Tests\Unit\HttpClient;

use Quantum\HttpClient\ResponseHeaders;
use Quantum\HttpClient\ResponseParser;
use Quantum\Tests\Unit\AppTestCase;

class ResponseParserTest extends AppTestCase
{
    public function testResponseParserParsesResponseHeaders(): void
    {
        $parser = new ResponseParser();

        $headers = $parser->parseHeaders(
            "HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\nContent-Type: application/json\r\nMalformed Header\r\nX-Test: one\r\nX-Test: two\r\n\r\n"
        );

        $this->assertInstanceOf(ResponseHeaders::class, $headers);
        $this->assertSame('HTTP/1.1 200 OK', $headers['status-line']);
        $this->assertSame('application/json', $headers['content-type']);
        $this->assertSame('one,two', $headers['x-test']);
    }

    public function testResponseParserReturnsEmptyHeadersWhenNoHttpHeaderBlockExists(): void
    {
        $parser = new ResponseParser();

        $this->assertCount(0, $parser->parseHeaders(''));
    }

    public function testResponseParserParsesCookie(): void
    {
        $parser = new ResponseParser();

        $cookie = $parser->parseCookie('Set-Cookie: sid=abc%20123; Path=/; HttpOnly');

        $this->assertSame(['name' => 'sid', 'value' => 'abc 123'], $cookie);
    }

    public function testResponseParserParsesEmptyCookieValue(): void
    {
        $parser = new ResponseParser();

        $cookie = $parser->parseCookie('Set-Cookie: sid=; Max-Age=0; Path=/');

        $this->assertSame(['name' => 'sid', 'value' => ''], $cookie);
    }

    public function testResponseParserReturnsNullForHeaderThatIsNotACookie(): void
    {
        $parser = new ResponseParser();

        $this->assertNull($parser->parseCookie('Content-Type: text/plain'));
    }

    public function testResponseParserParsesJsonBody(): void
    {
        $parser = new ResponseParser();
        $headers = $parser->parseHeaders("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n");

        $response = $parser->parseBody('{"ok":true}', $headers);

        $this->assertTrue($response->ok);
    }

    public function testResponseParserParsesXmlBody(): void
    {
        $parser = new ResponseParser();
        $headers = $parser->parseHeaders("HTTP/1.1 200 OK\r\nContent-Type: application/xml\r\n\r\n");

        $response = $parser->parseBody('<root><ok>yes</ok></root>', $headers);

        $this->assertSame('yes', (string) $response->ok);
    }

    public function testResponseParserReturnsRawBodyWhenXmlParsingFails(): void
    {
        $parser = new ResponseParser();
        $headers = $parser->parseHeaders("HTTP/1.1 200 OK\r\nContent-Type: application/xml\r\n\r\n");

        $previousUseInternalErrors = libxml_use_internal_errors(false);

        try {
            $response = $parser->parseBody('<root>', $headers);

            $this->assertSame('<root>', $response);
            $this->assertFalse(libxml_use_internal_errors());
        } finally {
            libxml_use_internal_errors($previousUseInternalErrors);
        }
    }

    public function testResponseParserDecodesGzipBody(): void
    {
        $parser = new ResponseParser();
        $headers = $parser->parseHeaders("HTTP/1.1 200 OK\r\nContent-Encoding: gzip\r\n\r\n");

        $response = $parser->parseBody(gzencode('compressed response'), $headers);

        $this->assertSame('compressed response', $response);
    }

    public function testResponseParserParsesGzippedJsonBody(): void
    {
        $parser = new ResponseParser();
        $headers = $parser->parseHeaders(
            "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Encoding: GZip\r\n\r\n"
        );

        $response = $parser->parseBody(gzencode('{"ok":true}'), $headers);

        $this->assertTrue($response->ok);
    }
}
