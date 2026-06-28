<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Asset;

use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Class Asset
 * @package Quantum\Asset
 */
class Asset
{
    /**
     * Type CSS
     */
    public const CSS = 1;

    /**
     * Type JS
     */
    public const JS = 2;

    private int $type;

    private string $path;

    private ?string $name;

    /**
     * @var array<string>|null
     */
    private ?array $attributes;

    private int $position;

    /**
     * Asset templates
     * @var string[]
     */
    private array $templates = [
        self::CSS => '<link rel="stylesheet" type="text/css" href="{%1}">',
        self::JS => '<script src="{%1}" {%2}></script>',
    ];

    /**
     * Asset constructor
     * @param array<string>|null $attributes
     */
    public function __construct(int $type, string $path, ?string $name = null, int $position = -1, ?array $attributes = [])
    {
        $this->type = $type;
        $this->path = $path;
        $this->name = $name;
        $this->position = $position;
        $this->attributes = $attributes;
    }

    /**
     * Gets asset type
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Gets an asset path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Gets asset name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Gets asset position
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Gets asset attributes
     * @return array<string>|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * Gets asset url
     * @throws DiException|ReflectionException
     */
    public function url(): string
    {
        if (!parse_url($this->path, PHP_URL_HOST)) {
            return base_url() . '/assets/' . $this->path;
        }

        return $this->path;
    }

    /**
     * Renders asset tag
     * @throws DiException|ReflectionException
     */
    public function tag(): string
    {
        return _message(
            $this->templates[$this->type],
            [$this->url(), implode(' ', $this->attributes ?? [])]
        ) . PHP_EOL;
    }
}
