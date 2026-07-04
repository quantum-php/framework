<?php

namespace Quantum\Tests\Unit\Renderer;

use Quantum\Renderer\Contracts\TemplateRendererInterface;
use Quantum\Renderer\Exceptions\RendererException;
use Quantum\Renderer\Adapters\TwigAdapter;
use Quantum\Renderer\Adapters\HtmlAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\MatchedRoute;
use Quantum\Renderer\Renderer;
use Quantum\Router\Route;

class RendererTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $route = new Route(
            ['GET'],
            '/test',
            'SomeController',
            'test'
        );
        $route->module('Test');

        $matchedRoute = new MatchedRoute($route, []);

        request()->create('GET', '/test');
        request()->setMatchedRoute($matchedRoute);
    }

    public function testRendererGetHtmlAdapter(): void
    {
        $renderer = new Renderer(new HtmlAdapter());

        $this->assertInstanceOf(HtmlAdapter::class, $renderer->getAdapter());

        $this->assertInstanceOf(TemplateRendererInterface::class, $renderer->getAdapter());
    }

    public function testRendererGetTwigAdapter(): void
    {
        if (!class_exists('Twig\Environment')) {
            $this->markTestSkipped('The optional twig/twig package is not installed.');
        }

        $renderer = new Renderer(new TwigAdapter());

        $this->assertInstanceOf(TwigAdapter::class, $renderer->getAdapter());

        $this->assertInstanceOf(TemplateRendererInterface::class, $renderer->getAdapter());
    }

    public function testRendererCallingValidMethod(): void
    {
        $renderer = new Renderer(new HtmlAdapter());

        $output = $renderer->render('index', ['name' => 'Tester']);

        $this->assertIsString($output);

        $this->assertSame('<p>Hello Tester, this is rendered html view</p>', $output);
    }

    public function testRendererCallingInvalidMethod(): void
    {
        $renderer = new Renderer(new HtmlAdapter());

        $this->expectException(RendererException::class);

        $this->expectExceptionMessage('The method `callingInvalidMethod` is not supported for `' . HtmlAdapter::class . '`');

        $renderer->callingInvalidMethod();

    }
}
