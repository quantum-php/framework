<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Renderer\Adapters;

use Quantum\Renderer\Contracts\TemplateRendererInterface;
use Quantum\Renderer\Exceptions\RendererException;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Twig\Loader\FilesystemLoader;
use Quantum\Storage\FileSystem;
use Twig\Error\RuntimeError;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use ReflectionException;
use Twig\TwigFunction;
use Twig\Environment;

/**
 * Class TwigAdapter
 * @package Quantum\Renderer
 */
class TwigAdapter implements TemplateRendererInterface
{
    protected FileSystem $fs;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $configs;

    /**
     * @param array<string, mixed>|null $configs
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function __construct(?array $configs = [])
    {
        $this->configs = $configs;

        $this->fs = FileSystemFactory::get();
    }

    /**
     * Renders the view
     * @param array<string, mixed> $params
     * @throws RendererException|LoaderError|DiException|BaseException|ReflectionException|RuntimeError|SyntaxError
     */
    public function render(string $view, array $params = []): string
    {
        $loader = $this->getLoader($view);

        $twig = new Environment($loader, $this->configs ?? []);

        $this->addFunctionsToTwig($twig);

        return $twig->render($view . '.php', $params);
    }

    /**
     * @throws RendererException|DiException|BaseException|ReflectionException
     */
    private function getLoader(string $view): FilesystemLoader
    {
        $moduleViewPath = modules_dir() . DS . request()->getCurrentModule() . DS . 'Views' . DS . $view . '.php';
        $sharedViewPath = base_dir() . DS . 'shared' . DS . 'views' . DS . $view . '.php';

        if ($this->fs->exists($moduleViewPath)) {
            return new FilesystemLoader(dirname($moduleViewPath));
        }

        if ($this->fs->exists($sharedViewPath)) {
            return new FilesystemLoader(dirname($sharedViewPath));
        }

        throw RendererException::fileNotFound($view);
    }

    private function addFunctionsToTwig(Environment $twig): void
    {
        $definedFunctions = get_defined_functions();
        $allDefinedFunctions = array_merge($definedFunctions['internal'], $definedFunctions['user']);

        foreach ($allDefinedFunctions as $function) {
            if (function_exists($function)) {
                $twig->addFunction(new TwigFunction($function, $function));
            }
        }
    }
}
