<?php

namespace Quantum\Tests\Unit\View\Helpers;

use Quantum\View\Factories\ViewFactory;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\MatchedRoute;
use Quantum\Router\Route;
use Quantum\View\View;

class ViewHelperTest extends AppTestCase
{
    private View $view;

    public function setUp(): void
    {
        parent::setUp();

        $route = new Route(
            ['GET', 'POST'],
            '/test',
            'TestController',
            'testAction'
        );
        $route->module('Test');

        $matchedRoute = new MatchedRoute($route, []);

        request()->create('POST', '/test');
        request()->setMatchedRoute($matchedRoute);

        $this->view = ViewFactory::get();
    }
    public function tearDown(): void
    {
        $this->view->setLayout(null);

        parent::tearDown();
    }

    public function testViewReturnsInstance(): void
    {
        $this->assertInstanceOf(View::class, view());
    }

    public function testViewGetContent(): void
    {
        $this->view->setLayout('layout');

        $this->view->render('index');

        $this->assertEquals('<p>Hello World, this is rendered html view</p>', view()->getContent());
    }

    public function testPartial(): void
    {
        $this->assertEquals('<p>Hello World, this is rendered partial html view</p>', partial('partial'));

        $this->assertEquals('<p>Hello John, this is rendered partial html view</p>', partial('partial', ['name' => 'John']));
    }

    public function testMarkdownToHtml(): void
    {
        $markdown = '**bold** text';
        $html = trim(markdown_to_html($markdown, true));

        $this->assertEquals('<p><strong>bold</strong> text</p>', $html);
    }
}
