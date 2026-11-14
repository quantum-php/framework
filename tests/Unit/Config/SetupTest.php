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
    }

    public function testSetupConstructor(): void
    {
        $setup = new Setup('config', 'database');

        $this->assertEquals('config', $setup->getPathPrefix());

        $this->assertEquals('database', $setup->getFilename());

        $this->assertEquals(true, $setup->getHierarchy());

        $this->assertEquals('File `' . $setup->getPathPrefix() . DS . $setup->getFilename() . '` not found!', $setup->getExceptionMessage());
    }

    public function testSetupUsesCurrentRequestModuleWhenModuleIsOmitted(): void
    {
        $this->testRequest('/');

        $setup = new Setup('config', 'dependencies');

        request()->setMatchedRoute(null);

        $this->assertSame('Test', $setup->getModule());
        $this->assertSame('Meme', (new Setup('config', 'dependencies', true, 'Meme'))->getModule());
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

    public function testSetGetHierarchy(): void
    {
        $this->setup->setHierarchy(true);

        $this->assertTrue($this->setup->getHierarchy());
    }

    public function testSetupRaisesNoDeprecationByDefault(): void
    {
        $messages = $this->collectDeprecations(function (): void {
            new Setup('config', 'app');
            new Setup('config', 'app', module: 'Test');
        });

        $this->assertSame([], $messages);
    }

    public function testSetupRaisesDeprecationWhenHierarchicalArgumentIsPassed(): void
    {
        $messages = $this->collectDeprecations(function (): void {
            new Setup('config', 'app', false);
        });

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('$hierarchical argument', $messages[0]);
        $this->assertStringContainsString('3.2.0', $messages[0]);
    }

    public function testSetupKeepsHierarchicalValueWhenArgumentIsPassed(): void
    {
        $setup = null;

        $this->collectDeprecations(function () use (&$setup): void {
            $setup = new Setup('config', 'app', false);
        });

        $this->assertFalse($setup->getHierarchy());
    }

    public function testSetHierarchyRaisesDeprecation(): void
    {
        $messages = $this->collectDeprecations(function (): void {
            $this->setup->setHierarchy(false);
        });

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('setHierarchy()', $messages[0]);
        $this->assertStringContainsString('3.2.0', $messages[0]);
        $this->assertFalse($this->setup->getHierarchy());
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
