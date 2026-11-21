<?php

namespace Quantum\Tests\Unit\Validation\Traits;

use Quantum\Tests\Unit\AppTestCase;
use Quantum\Validation\Validator;
use Quantum\Validation\Rule;
use Quantum\Http\Request;

class FileRuleTest extends AppTestCase
{
    public Validator $validator;
    private Request $request;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = request();

        $this->validator = new Validator();

        $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
            . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
            . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
            . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';

        $img = imagecreatefromstring(base64_decode($data));

        imagepng($img, base_dir() . DS . 'php8fe2.tmp');

        imagedestroy($img);
    }

    public function tearDown(): void
    {
        $this->request->flush();

        unlink(base_dir() . DS . 'php8fe2.tmp');

        parent::tearDown();
    }

    public function testRuleFileSize(): void
    {
        $file = [
            'image' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => base_dir() . DS . 'php8fe2.tmp',
                'type' => 'image/jpg',
                'error' => 0,
            ],
        ];

        $this->request->create('POST', '/upload', [], [], $file);

        $this->validator->setRule('image', [
            Rule::fileSize(1000),
        ]);

        $this->assertTrue($this->validator->isValid($this->request->all()));

        $this->validator->setRule('image', [
            Rule::fileSize(9000, 500),
        ]);

        $this->assertFalse($this->validator->isValid($this->request->all()));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.fileSize', $errors['image'][0]);
    }

    public function testRuleFileMimeType(): void
    {
        $file = [
            'image' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => base_dir() . DS . 'php8fe2.tmp',
                'type' => 'image/jpg',
                'error' => 0,
            ],
        ];

        $this->request->create('POST', '/upload', [], [], $file);

        $this->validator->setRule('image', [
            Rule::fileMimeType('image/png'),
        ]);

        $this->assertTrue($this->validator->isValid($this->request->all()));

        $this->validator->setRule('image', [
            Rule::fileMimeType('image/jpg', 'image/jpeg', 'image/png'),
        ]);

        $this->assertTrue($this->validator->isValid($this->request->all()));

        $this->validator->setRule('image', [
            Rule::fileMimeType('image/gif', 'image/jpg'),
        ]);

        $this->assertFalse($this->validator->isValid($this->request->all()));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.fileMimeType', $errors['image'][0]);
    }

    public function testRuleFileExtension(): void
    {
        $file = [
            'image' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => base_dir() . DS . 'php8fe2.tmp',
                'type' => 'image/jpg',
                'error' => 0,
            ],
        ];

        $this->request->create('POST', '/upload', [], [], $file);

        $this->validator->setRule('image', [
            Rule::fileExtension('jpg'),
        ]);

        $this->assertTrue($this->validator->isValid($this->request->all()));

        $this->validator->setRule('image', [
            Rule::fileExtension('png', 'jpg', 'gif'),
        ]);

        $this->assertTrue($this->validator->isValid($this->request->all()));

        $this->validator->setRule('image', [
            Rule::fileExtension('exe', 'bmp'),
        ]);

        $this->assertFalse($this->validator->isValid($this->request->all()));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.fileExtension', $errors['image'][0]);
    }

    public function testRuleImageDimensions(): void
    {
        $file = [
            'image' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => base_dir() . DS . 'php8fe2.tmp',
                'type' => 'image/jpg',
                'error' => 0,
            ],
        ];

        $this->request->create('POST', '/upload', [], [], $file);

        $this->validator->setRule('image', [
            Rule::imageDimensions(28, 18),
        ]);

        $this->assertTrue($this->validator->isValid($this->request->all()));

        $this->validator->setRule('image', [
            Rule::imageDimensions(300, 500),
        ]);

        $this->assertFalse($this->validator->isValid($this->request->all()));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.imageDimensions', $errors['image'][0]);
    }
}
