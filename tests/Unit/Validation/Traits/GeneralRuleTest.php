<?php

namespace Quantum\Tests\Unit\Validation\Traits;

use Quantum\Tests\_root\shared\Models\TestUserModel;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Validation\Validator;
use Quantum\Database\Database;
use Quantum\Validation\Rule;
use Quantum\Config\Setup;

class GeneralRuleTest extends AppTestCase
{
    public Validator $validator;
    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new Validator();

        if (!config()->has('database')) {
            config()->import(new Setup('config', 'database', true));
        }

        config()->set('database.default', 'sqlite');

        config()->set('debug', true);
    }

    public function tearDown(): void
    {
        Database::execute('DROP TABLE IF EXISTS users');

        parent::tearDown();
    }

    public function testRuleRequired(): void
    {
        $this->validator->setRule('text', [
            Rule::required(),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => 'something']));

        $this->assertFalse($this->validator->isValid(['text' => '']));

        $this->assertFalse($this->validator->isValid(['custom' => 'customValue']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.required', $errors['text'][0]);
    }

    public function testRuleEmail(): void
    {
        $this->validator->setRule('text', [
            Rule::email(),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => 'something@mail.com']));

        $this->assertFalse($this->validator->isValid(['text' => 'something']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.email', $errors['text'][0]);
    }

    public function testRuleCreditCard(): void
    {
        $this->validator->setRule('text', [
            Rule::creditCard(),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => '4111111111111111']));

        $this->assertFalse($this->validator->isValid(['text' => '123456789876541']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.creditCard', $errors['text'][0]);
    }

    public function testRuleIban(): void
    {
        $this->validator->setRule('text', [
            Rule::iban(),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => 'DE75512108001245126199']));

        $this->assertTrue($this->validator->isValid(['text' => 'FR7630006000011234567890189']));

        $this->assertFalse($this->validator->isValid(['text' => 'XR763000']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.iban', $errors['text'][0]);
    }

    public function testRuleName(): void
    {
        $this->validator->setRule('text', [
            Rule::name(),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => 'John']));

        $this->assertTrue($this->validator->isValid(['text' => 'jim']));

        $this->assertTrue($this->validator->isValid(['text' => 'Jane Marko']));

        $this->assertFalse($this->validator->isValid(['text' => 'jim 12']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.name', $errors['text'][0]);
    }

    public function testRuleStreetAddress(): void
    {
        $this->validator->setRule('text', [
            Rule::streetAddress(),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => 'Barbara ave. 12']));

        $this->assertTrue($this->validator->isValid(['text' => 'Lincoln blvd. 22']));

        $this->assertFalse($this->validator->isValid(['text' => 'something']));

        $this->assertFalse($this->validator->isValid(['text' => 123]));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.streetAddress', $errors['text'][0]);
    }

    public function testRulePhoneNumber(): void
    {
        $this->validator->setRule('text', [
            Rule::phoneNumber(),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => '1-555-555-5555']));

        $this->assertTrue($this->validator->isValid(['text' => '+1-555-555-5555']));

        $this->assertTrue($this->validator->isValid(['text' => '1 (519) 555-4422']));

        $this->assertTrue($this->validator->isValid(['text' => '+1 (519) 555-4422']));

        $this->assertTrue($this->validator->isValid(['text' => '555 555 5555']));

        $this->assertTrue($this->validator->isValid(['text' => '555-555-5555']));

        $this->assertFalse($this->validator->isValid(['text' => '55-555-5555']));

        $this->assertFalse($this->validator->isValid(['text' => '5 55 555']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.phoneNumber', $errors['text'][0]);
    }

    public function testRuleDate(): void
    {
        $this->validator->setRule('text', [
            Rule::date(),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => '2020-05-09']));

        $this->assertTrue($this->validator->isValid(['text' => '2020-05-09 01:58:00']));

        $this->validator->setRule('text', [
            Rule::date('m/d/Y'),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => '05/09/2020']));

        $this->validator->setRule('text', [
            Rule::date('m/d/Y H:i:s'),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => '05/09/2020 01:59:00']));

        $this->assertFalse($this->validator->isValid(['text' => '05.09.2020']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.date', $errors['text'][0]);
    }

    public function testRuleStarts(): void
    {
        $this->validator->setRule('text', [
            Rule::starts('b'),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => 'bike']));

        $this->validator->setRule('text', [
            Rule::starts('br'),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => 'break']));

        $this->assertFalse($this->validator->isValid(['text' => 'about']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.starts', $errors['text'][0]);
    }

    public function testRuleRegex(): void
    {
        $this->validator->setRule('text', [
            Rule::regex('/^[1-9]+$/'),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => 123]));

        $this->validator->setRule('text', [
            Rule::regex('/^[a-zA-Z]+$/'),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => 'Hello']));

        $this->assertFalse($this->validator->isValid(['text' => 'Hello world']));

        $this->assertFalse($this->validator->isValid(['text' => 'Hello 123']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.regex', $errors['text'][0]);
    }

    public function testRuleJsonString(): void
    {
        $this->validator->setRule('text', [
            Rule::jsonString(),
        ]);

        $this->assertTrue($this->validator->isValid(['text' => '{"widget": {"debug": "on", "version": "1.0"}}']));

        $this->assertFalse($this->validator->isValid(['text' => '{hello: world}']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.jsonString', $errors['text'][0]);
    }

    public function testRuleSame(): void
    {
        $this->validator->setRule('password', [
            Rule::same('confirm_password'),
        ]);

        $this->assertTrue($this->validator->isValid(['password' => 'qwerty', 'confirm_password' => 'qwerty']));

        $this->assertFalse($this->validator->isValid(['password' => 'qwerty', 'confirm_password' => '123456']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.same', $errors['password'][0]);
    }

    public function testRuleUnique(): void
    {
        $this->createUsersTable();

        $this->validator->setRule('username', [
            Rule::unique(TestUserModel::class, 'username'),
        ]);

        $this->assertTrue($this->validator->isValid(['username' => 'john@doe']));

        $this->insertTestUser();

        $this->assertFalse($this->validator->isValid(['username' => 'john@doe']));

        $errors = $this->validator->getErrors();

        $this->assertNotEmpty($errors);

        $this->assertEquals('validation.unique', $errors['username'][0]);
    }

    public function testRuleExists(): void
    {
        $this->createUsersTable();

        $this->validator->setRule('username', [
            Rule::exists(TestUserModel::class, 'username'),
        ]);

        $this->assertFalse($this->validator->isValid(['username' => 'john@doe']));

        $errors = $this->validator->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertEquals('validation.exists', $errors['username'][0]);

        $this->insertTestUser();

        $this->assertTrue($this->validator->isValid(['username' => 'john@doe']));

        $errors = $this->validator->getErrors();
        $this->assertEmpty($errors);
    }

    protected function createUsersTable(): void
    {
        Database::execute('
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(255),
            password VARCHAR(255),
            created_at DATETIME
        )
    ');
    }

    protected function insertTestUser(string $username = 'john@doe', string $password = '123456'): void
    {
        $model = ModelFactory::get(TestUserModel::class);
        $model->create();
        $model->username = $username;
        $model->password = $password;
        $model->save();
    }
}
