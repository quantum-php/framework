<?php

namespace Quantum\Tests\Unit\Renderer\Adapters;

use Quantum\Renderer\Adapters\TwigAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\ConfigSection;
use Quantum\Router\MatchedRoute;
use Twig\Error\RuntimeError;
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
        $adapter = new TwigAdapter();

        $output = $adapter->render('index.twig', ['name' => 'Tester']);

        $this->assertIsString($output);

        $this->assertSame('<p>Hello Tester, this is rendered twig view</p>', $output);
    }

    public function testTwigAdapterPassesItsConfigToTwig(): void
    {
        $template = PROJECT_ROOT . DS . 'modules' . DS . 'Test' . DS . 'Views' . DS . 'strict_probe.twig.php';
        $this->assertFileDoesNotExist($template);

        try {
            $this->createFile($template, '<p>{{ missing }}</p>');

            $this->assertSame('<p></p>', (new TwigAdapter())->render('strict_probe.twig'));

            $this->expectException(RuntimeError::class);

            (new TwigAdapter(new ConfigSection(['strict_variables' => true])))->render('strict_probe.twig');
        } finally {
            $this->removeFile($template);
        }
    }
}
