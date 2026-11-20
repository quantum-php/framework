<?php

namespace Quantum\Tests\Unit\Http\Traits\Response;

use Quantum\Tests\Unit\AppTestCase;

class HttpResponseBodyTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        response()->flush();

        parent::tearDown();
    }

    public function testResponseSetHasGetAllDelete(): void
    {
        $response = response();

        $this->assertEmpty($response->all());

        $this->assertFalse($response->has('name'));

        $returned = $response->set('name', 'John');

        $this->assertSame($response, $returned);

        $this->assertTrue($response->has('name'));

        $this->assertEquals('John', $response->get('name'));

        $this->assertIsArray($response->all());

        $response->delete('name');

        $this->assertFalse($response->has('name'));

        $this->assertNull($response->get('name'));

        $this->assertEquals('Jane', $response->get('name', 'Jane'));
    }

    public function testResponseJsonContent(): void
    {
        $response = response();

        $response->set('firstname', 'John');

        $response->set('lastname', 'Doe');

        $this->assertSame($response, $response->json());

        $this->assertEquals('{"firstname":"John","lastname":"Doe"}', $response->getContent());

        $response->set('age', 25);

        $this->assertEquals('{"firstname":"John","lastname":"Doe","age":25}', $response->getContent());

        $response->json([
            'gender' => 'male',
            'role' => 'user',
        ]);

        $this->assertEquals('{"firstname":"John","lastname":"Doe","age":25,"gender":"male","role":"user"}', $response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testResponseJsonP(): void
    {
        $response = response();

        $response->set('firstname', 'John');

        $response->set('lastname', 'Doe');

        $this->assertSame($response, $response->jsonp('myfunc'));

        $this->assertEquals('myfunc({"firstname":"John","lastname":"Doe"})', $response->getContent());
    }

    public function testResponseXmlContent(): void
    {
        $response = response();

        $response->set('firstname', 'John');

        $response->set('lastname', 'Doe');

        $this->assertSame($response, $response->xml());

        $xml = "<?xml version=\"1.0\"?>\n" .
            "<data>\n" .
            "  <firstname>John</firstname>\n" .
            "  <lastname>Doe</lastname>\n" .
            "</data>\n";

        $this->assertEquals($xml, $response->getContent());

        $response->set('age', 25);

        $xml = "<?xml version=\"1.0\"?>\n" .
            "<data>\n" .
            "  <firstname>John</firstname>\n" .
            "  <lastname>Doe</lastname>\n" .
            "  <age>25</age>\n" .
            "</data>\n";

        $this->assertEquals($xml, $response->getContent());

        $response->xml([
            'gender' => 'male',
            'role' => 'user',
        ]);

        $xml = "<?xml version=\"1.0\"?>\n" .
            "<data>\n" .
            "  <firstname>John</firstname>\n" .
            "  <lastname>Doe</lastname>\n" .
            "  <age>25</age>\n" .
            "  <gender>male</gender>\n" .
            "  <role>user</role>\n" .
            "</data>\n";

        $this->assertEquals($xml, $response->getContent());
    }

    public function testResponseXmlWithNestedArray(): void
    {
        $response = response();

        $response->xml([
            'article' => [
                'title' => 'Todays news',
                'description' => 'News content',
            ],
        ]);

        $xml = "<?xml version=\"1.0\"?>\n" .
            "<data>\n" .
            "  <article>\n" .
            "    <title>Todays news</title>\n" .
            "    <description>News content</description>\n" .
            "  </article>\n" .
            "</data>\n";

        $this->assertEquals($xml, $response->getContent());
    }

    public function testResponseXmlWithArguments(): void
    {
        $response = response();

        $response->xml([
            'article@{"type":"post"}' => [
                'title' => 'Todays news',
                'description@{"content":"html"}' => 'News content',
            ],
        ]);

        $xml = "<?xml version=\"1.0\"?>\n" .
            "<data>\n" .
            "  <article type=\"post\">\n" .
            "    <title>Todays news</title>\n" .
            "    <description content=\"html\">News content</description>\n" .
            "  </article>\n" .
            "</data>\n";

        $this->assertEquals($xml, $response->getContent());
    }

    public function testResponseXmlWithCustomRoot(): void
    {
        $response = response();

        $response->xml([
            'article@{"type":"post"}' => [
                'title' => 'Todays news',
                'description@{"content":"html"}' => 'News content',
            ],
        ], '<custom></custom>', 200);

        $xml = "<?xml version=\"1.0\"?>\n" .
            "<custom>\n" .
            "  <article type=\"post\">\n" .
            "    <title>Todays news</title>\n" .
            "    <description content=\"html\">News content</description>\n" .
            "  </article>\n" .
            "</custom>\n";

        $this->assertEquals($xml, $response->getContent());
    }

    public function testResponseHtmlContent(): void
    {
        $response = response();

        $this->assertSame($response, $response->html('<div>John Doe</div>'));

        $this->assertEquals('<div>John Doe</div>', $response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
    }
}
