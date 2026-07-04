<?php

namespace Quantum\Tests\Unit\Renderer\Adapters;

use Quantum\Renderer\Adapters\TwigAdapter;
use Quantum\Renderer\Exceptions\RendererException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\MatchedRoute;
use Quantum\Router\Route;

class TwigAdapterTest extends AppTestCase
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

    public function testTwigAdapterRenderView(): void
    {
        if (!class_exists('Twig\Environment')) {
            $this->markTestSkipped('The optional twig/twig package is not installed.');
        }

        $adapter = new TwigAdapter();

        $output = $adapter->render('index.twig', ['name' => 'Tester']);

        $this->assertIsString($output);

        $this->assertSame('<p>Hello Tester, this is rendered twig view</p>', $output);
    }

    public function testTwigAdapterThrowsActionableExceptionWhenTwigIsMissing(): void
    {
        if (class_exists('Twig\Environment')) {
            $this->markTestSkipped('The optional twig/twig package is installed.');
        }

        $this->expectException(RendererException::class);
        $this->expectExceptionMessage(
            'The Twig renderer requires the optional `twig/twig` package. ' .
            'Install a security-supported release separately; Twig ^3.27 requires PHP 8.1 or later: ' .
            '`composer require twig/twig:^3.27`.'
        );

        new TwigAdapter();
    }
}
