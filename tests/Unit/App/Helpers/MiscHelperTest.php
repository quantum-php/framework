<?php

namespace Quantum\Tests\Unit\App\Helpers;

use Quantum\Tests\Unit\AppTestCase;

class MiscHelperTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testMessageHelper(): void
    {
        $this->assertEquals('Hello John', _message('Hello {%1}', 'John'));

        $this->assertEquals('Hello John, greetings from Jenny', _message('Hello {%1}, greetings from {%2}', ['John', 'Jenny']));
    }

    public function testValidBase64(): void
    {
        $validBase64String = base64_encode('test');

        $invalidBase64String = 'abc123';

        $this->assertTrue(valid_base64($validBase64String));

        $this->assertFalse(valid_base64($invalidBase64String));
    }

    public function testRandomNumber(): void
    {
        $this->assertIsInt(random_number());

        $this->assertIsInt(random_number(5));
    }

    public function testSlugify(): void
    {
        $this->assertEquals('text-with-spaces', slugify('Text with spaces'));

        $this->assertEquals('ebay-com-itm-dual-arm-tv-trkparms-aid-3d111001-26brand-3dunbranded-trksid-p2380057', slugify('ebay.com/itm/DUAL-ARM-TV/?_trkparms=aid%3D111001%26brand%3DUnbranded&_trksid=p2380057'));
    }

    public function testIsDebugModeIsOffWhenTheDebugKeyIsNotSet(): void
    {
        config()->flush();

        $this->assertFalse(is_debug_mode());
    }
}
