<?php

namespace Quantum\Tests\Unit\Lang;

use Quantum\Lang\Adapters\FileAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\MatchedRoute;
use Quantum\Router\Route;
use Quantum\Lang\Lang;

class LangTest extends AppTestCase
{
    private Lang $lang;

    public function setUp(): void
    {
        parent::setUp();

        $this->lang = new Lang('en', true, new FileAdapter('en'));

        $route = new Route(
            ['POST'],
            '/api-signin',
            'SomeController',
            'signin'
        );
        $route->module('Test');

        $matchedRoute = new MatchedRoute($route, []);

        request()->create('POST', '/api-signin');
        request()->setMatchedRoute($matchedRoute);
    }

    public function testLangGetSet(): void
    {
        $this->assertEquals('en', $this->lang->getLang());

        $this->lang->setLang('ru');

        $this->assertEquals('ru', $this->lang->getLang());
    }

    public function testLangIsEnabled(): void
    {
        $this->assertTrue($this->lang->isEnabled());

        $langDisabled = new Lang('en', false, new FileAdapter('en'));

        $this->assertFalse($langDisabled->isEnabled());
    }

    public function testLangLoadAndGetTranslation(): void
    {
        $this->lang->flush();

        $this->assertEquals('custom.test', $this->lang->getTranslation('custom.test'));

        $this->lang->load();

        $this->assertEquals('Testing', $this->lang->getTranslation('custom.test'));

        $this->assertEquals('Information about the new feature', $this->lang->getTranslation('custom.info', ['new']));
    }

    public function testLangFlushResetsTranslations(): void
    {
        $this->lang->load();

        $this->assertEquals('Testing', $this->lang->getTranslation('custom.test'));

        $this->lang->flush();

        $this->assertEquals('test', $this->lang->getTranslation('test'));
    }
}
