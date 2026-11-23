<?php

namespace Quantum\Tests\Unit\Config;

use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\Setup;

class SetupTest extends AppTestCase
{
    private Setup $setup;

    public function setUp(): void
    {
        parent::setUp();

        $this->setup = new Setup();
    }

    public function tearDown(): void
    {
        unset($this->setup);

        parent::tearDown();
    }

    public function testSetupConstructor(): void
    {
        $setup = new Setup('config', 'database');

        $this->assertEquals('config', $setup->getPathPrefix());

        $this->assertEquals('database', $setup->getFilename());

        $this->assertEquals('File `' . $setup->getPathPrefix() . DS . $setup->getFilename() . '` not found!', $setup->getExceptionMessage());
    }

    public function testSetupUsesCurrentRequestModuleWhenModuleIsOmitted(): void
    {
        $this->testRequest('/');

        $setup = new Setup('config', 'dependencies');

        request()->setMatchedRoute(null);

        $this->assertSame('Test', $setup->getModule());
        $this->assertSame('Meme', (new Setup('config', 'dependencies', module: 'Meme'))->getModule());
    }

    public function testDefaultExceptionMessageIsCapturedAtConstruction(): void
    {
        $setup = new Setup('config', 'app');

        $setup->setPathPrefix('other')->setFilename('changed');

        $this->assertSame('File `config' . DS . 'app` not found!', $setup->getExceptionMessage());
    }

    public function testSetGetPathPrefix(): void
    {
        $this->setup->setPathPrefix('config');

        $this->assertEquals('config', $this->setup->getPathPrefix());
    }

    public function testSetGetFilename(): void
    {
        $this->setup->setFilename('users');

        $this->assertEquals('users', $this->setup->getFilename());
    }

    public function testSetupRaisesNoDeprecationByDefault(): void
    {
        $messages = $this->collectDeprecations(function (): void {
            new Setup('config', 'app');
            new Setup('config', 'app', module: 'Test');
        });

        $this->assertSame([], $messages);
    }

    public function testSetGetModule(): void
    {
        $this->setup->setModule('admin');

        $this->assertEquals('admin', $this->setup->getModule());
    }

    public function testSetGetExceptionMessage(): void
    {
        $this->setup->setExceptionMessage('Action not allowed');

        $this->assertEquals('Action not allowed', $this->setup->getExceptionMessage());
    }

    public function testThirdArgumentIsTheModule(): void
    {
        $setup = new Setup('config', 'app', 'Blog');

        $this->assertSame('Blog', $setup->getModule());
    }

    public function testFourthArgumentIsTheExceptionMessage(): void
    {
        $setup = new Setup('config', 'app', 'Blog', 'App config is missing');

        $this->assertSame('Blog', $setup->getModule());
        $this->assertSame('App config is missing', $setup->getExceptionMessage());
    }

    public function testHierarchyMethodsAreRemoved(): void
    {
        $this->assertFalse(method_exists(Setup::class, 'setHierarchy'));
        $this->assertFalse(method_exists(Setup::class, 'getHierarchy'));
    }

    /**
     * @return array<int, string>
     */
    private function collectDeprecations(callable $callback): array
    {
        $messages = [];

        set_error_handler(function (int $errno, string $errstr) use (&$messages): bool {
            $messages[] = $errstr;
            return true;
        }, E_USER_DEPRECATED);

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $messages;
    }
}
