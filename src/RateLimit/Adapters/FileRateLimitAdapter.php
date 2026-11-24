<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\RateLimit\Adapters;

use Quantum\RateLimit\Contracts\RateLimitAdapterInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Config\ConfigSection;
use Quantum\Storage\FileSystem;
use ReflectionException;

/**
 * Class FileRateLimitAdapter
 * @package Quantum\RateLimit
 */
class FileRateLimitAdapter implements RateLimitAdapterInterface
{
    private readonly FileSystem $fs;

    private readonly int $resetInterval;

    private readonly string $path;

    private readonly string $prefix;

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function __construct(ConfigSection $config)
    {
        if (!$config->has('ttl')) {
            throw ConfigException::missingKey('ttl');
        }

        $this->resetInterval = $config->getInt('ttl');
        $this->path = $config->get('path');
        $this->prefix = (string) $config->get('prefix', '');
        $this->fs = fs();

        if (!$this->fs->isDirectory($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function hit(string $key, int $limit, int $interval): bool
    {
        $now = time();
        $statePath = $this->getStatePath($key);
        $lockPath = $this->getLockPath($key);
        $lockHandle = fopen($lockPath, 'c+');

        if ($lockHandle === false) {
            return false;
        }

        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            return false;
        }

        try {
            $data = $this->readState($statePath);

            if (!is_array($data) || !isset($data['count'], $data['reset_at']) || $now >= (int) $data['reset_at']) {
                $count = 0;
                $resetAt = $now + $interval;
            } else {
                $count = $data['count'];
                $resetAt = $data['reset_at'];
            }

            $count++;

            $this->writeState($statePath, [
                'count' => $count,
                'reset_at' => $resetAt,
            ]);
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }

        return $count <= $limit;
    }

    public function reset(string $key, int $count = 0): void
    {
        $statePath = $this->getStatePath($key);

        if ($count <= 0) {
            if ($this->fs->exists($statePath)) {
                $this->fs->remove($statePath);
            }
            return;
        }

        $this->writeState($statePath, [
            'count' => $count,
            'reset_at' => time() + $this->resetInterval,
        ]);
    }

    public function retryAfter(string $key): int
    {
        $data = $this->readState($this->getStatePath($key));

        if (!is_array($data) || !isset($data['reset_at'])) {
            return 0;
        }

        return max(0, (int) $data['reset_at'] - time());
    }

    private function getStatePath(string $key): string
    {
        return $this->path . DS . md5($this->prefix . $key) . '.rate';
    }

    private function getLockPath(string $key): string
    {
        return $this->path . DS . md5($this->prefix . $key) . '.lock';
    }

    /**
     * @return array<string, int>|null
     */
    private function readState(string $statePath): ?array
    {
        if (!$this->fs->exists($statePath)) {
            return null;
        }

        $raw = $this->fs->get($statePath);

        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, int> $state
     */
    private function writeState(string $statePath, array $state): void
    {
        $this->fs->put($statePath, (string) json_encode($state));
    }
}
