<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Asset;

use Quantum\Asset\Exceptions\AssetException;
use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Class AssetFactory
 * @package Quantum\Asset
 */
class AssetManager
{
    /**
     * Assets store types
     */
    public const STORES = [
        'css' => Asset::CSS,
        'js' => Asset::JS,
    ];

    /**
     * Asset store
     * @var Asset[]
     */
    private array $store = [];

    /**
     * Published assets
     * @var array<int, array<int, Asset>>
     */
    private array $published = [];

    public function __construct()
    {
        foreach (self::STORES as $type) {
            $this->published[$type] = [];
        }
    }

    /**
     * Gets the asset by name
     */
    public function get(string $name): ?Asset
    {
        foreach ($this->store as $asset) {
            if ($asset->getName() == $name) {
                return $asset;
            }
        }

        return null;
    }

    /**
     * Asset url
     * @throws DiException|ReflectionException
     */
    public function url(string $path): string
    {
        if (!parse_url($path, PHP_URL_HOST)) {
            return base_url() . '/assets/' . $path;
        }

        return $path;
    }

    /**
     * Register assets
     * @param Asset[] $assets
     * @throws AssetException
     */
    public function register(array $assets): void
    {
        foreach ($assets as $asset) {
            $this->registerAsset($asset);
        }
    }

    /**
     * Register single asset
     * @throws AssetException
     */
    public function registerAsset(Asset $asset): void
    {
        if ($asset->getName() && $this->get($asset->getName())) {
            throw AssetException::nameInUse($asset->getName());
        }

        $this->store[] = $asset;
    }

    public function flush(): void
    {
        $this->store = [];
        $this->published = [];
    }

    /**
     * Dumps the assets
     * @throws AssetException|DiException|ReflectionException
     */
    public function dump(int $type): void
    {
        if (empty($this->published[$type])) {
            $this->publish();
        }

        if ($this->published && count($this->published[$type])) {
            foreach ($this->published[$type] as $asset) {
                echo $asset->tag();
            }
        }
    }

    /**
     * Publishes assets
     * @throws AssetException
     */
    private function publish(): void
    {
        if ($this->store !== []) {
            $this->setPriorityAssets();
            $this->setRegularAssets();

            ksort($this->published[Asset::CSS]);
            ksort($this->published[Asset::JS]);
        }
    }

    /**
     * Sets assets with ordered position
     * @throws AssetException
     */
    private function setPriorityAssets(): void
    {
        foreach ($this->store as $asset) {
            $position = $asset->getPosition();
            $type = $asset->getType();

            if ($position !== -1) {
                if (isset($this->published[$type][$position])) {
                    throw AssetException::positionInUse($position, $asset->getPath());
                }

                $this->published[$type][$position] = $asset;
            }
        }
    }

    /**
     * Sets assets without ordered position
     */
    private function setRegularAssets(): void
    {
        foreach ($this->store as $asset) {
            if ($asset->getPosition() === -1) {
                $this->setPosition($asset, 0);
            }
        }
    }

    /**
     * Sets the Position
     */
    private function setPosition(Asset $asset, int $index): void
    {
        $type = $asset->getType();

        if (isset($this->published[$type][$index])) {
            $this->setPosition($asset, $index + 1);
        } else {
            $this->published[$type][$index] = $asset;
        }
    }
}
