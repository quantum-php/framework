<?php

namespace Quantum\Tests\Unit\Renderer\Factories;

use Quantum\Renderer\Contracts\TemplateRendererInterface;
use Quantum\Renderer\Exceptions\RendererException;
use Quantum\Renderer\Factories\RendererFactory;
use Quantum\Renderer\Adapters\HtmlAdapter;
use Quantum\Renderer\Adapters\TwigAdapter;
use Quantum\Renderer\Enums\RendererType;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Renderer\Renderer;
use Quantum\Di\Di;

class RendererFactoryTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->resetRendererFactory();
    }

    public function testRendererFactoryInstance(): void
    {
        $renderer = RendererFactory::get();

        $this->assertInstanceOf(Renderer::class, $renderer);
    }

    public function testRendererFactoryGetDefaultAdapter(): void
    {
        $renderer = RendererFactory::get();

        $this->assertInstanceOf(TemplateRendererInterface::class, $renderer->getAdapter());

        $this->assertInstanceOf(HtmlAdapter::class, $renderer->getAdapter());
    }

    public function testRendererFactoryGetHtmlAdapter(): void
    {
        $renderer = RendererFactory::get(RendererType::HTML);

        $this->assertInstanceOf(TemplateRendererInterface::class, $renderer->getAdapter());

        $this->assertInstanceOf(HtmlAdapter::class, $renderer->getAdapter());
    }

    public function testRendererFactoryTwigAdapter(): void
    {
        if (!class_exists('Twig\Environment')) {
            $this->markTestSkipped('The optional twig/twig package is not installed.');
        }

        $renderer = RendererFactory::get(RendererType::TWIG);

        $this->assertInstanceOf(TemplateRendererInterface::class, $renderer->getAdapter());

        $this->assertInstanceOf(TwigAdapter::class, $renderer->getAdapter());
    }

    public function testRendererFactoryInvalidTypeAdapter(): void
    {
        $this->expectException(RendererException::class);

        $this->expectExceptionMessage('The adapter `invalid_type` is not supported');

        RendererFactory::get('invalid_type');
    }

    public function testRendererFactoryReturnsSameInstance(): void
    {
        $renderer1 = RendererFactory::get(RendererType::HTML);
        $renderer2 = RendererFactory::get(RendererType::HTML);

        $this->assertSame($renderer1, $renderer2);
    }

    private function resetRendererFactory(): void
    {
        if (!Di::isRegistered(RendererFactory::class)) {
            Di::register(RendererFactory::class);
        }

        $factory = Di::get(RendererFactory::class);
        $this->setPrivateProperty($factory, 'instances', []);
    }
}
