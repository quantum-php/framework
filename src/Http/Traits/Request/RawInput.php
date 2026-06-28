<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Request;

use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Enums\ContentType;
use Quantum\Storage\UploadedFile;
use ReflectionException;

/**
 * Class RawInput
 * @package Quantum\Http
 */
trait RawInput
{
    /**
     * Parses raw input data and returns parsed parameters and files
     * @return array<string, mixed>
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function parse(string $rawInput): array
    {
        $boundary = $this->getBoundary();

        if (!$boundary) {
            return ['params' => [], 'files' => []];
        }

        $blocks = $this->getBlocks($boundary, $rawInput);

        return $this->processBlocks($blocks);
    }

    /**
     * Extracts boundary string from Content-Type header
     */
    private function getBoundary(): ?string
    {
        $contentType = server()->contentType();

        if (!$contentType) {
            return null;
        }

        preg_match('/boundary=(.*)$/', $contentType, $match);

        return $match[1] ?? null;
    }

    /**
     * Splits raw input into multipart blocks
     * @return array<string>
     */
    private function getBlocks(string $boundary, string $rawInput): array
    {
        $result = preg_split("/-+$boundary/", $rawInput);

        if ($result === false) {
            return [];
        }

        array_pop($result);

        return $result;
    }

    /**
     * Processes multipart blocks and extracts parameters and files
     * @param array<string> $blocks
     * @return array<string, mixed>
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    private function processBlocks(array $blocks): array
    {
        $params = [];
        $files = [];

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            $type = $this->detectBlockType($block);

            switch ($type) {
                case 'file':
                    $parsed = $this->getParsedFile($block);

                    if ($parsed === null) {
                        continue 2;
                    }

                    [$nameParam, $file] = $parsed;

                    $this->addFileToCollection($files, $nameParam, $file);
                    break;

                case 'stream':
                    $params += $this->getParsedStream($block);
                    break;

                case 'param':
                default:
                    $params += $this->getParsedParameter($block);
                    break;
            }
        }

        return ['params' => $params, 'files' => $files];
    }

    /**
     * Adds a parsed file to the files collection
     * @param array<string, mixed> $files
     */
    private function addFileToCollection(array &$files, string $nameParam, UploadedFile $file): void
    {
        $arrayParam = $this->arrayParam($nameParam);

        if (is_array($arrayParam)) {
            [$name, $key] = $arrayParam;

            if ($key === '') {
                $files[$name][] = $file;
            } else {
                $files[$name][$key] = $file;
            }
        } else {
            $files[$nameParam] = $file;
        }
    }

    /**
     * Detects the block type as a string identifier.
     * @return string One of 'file', 'stream', 'param'
     */
    private function detectBlockType(string $block): string
    {
        if (str_contains($block, 'filename')) {
            return 'file';
        }

        if (str_contains($block, ContentType::OCTET_STREAM)) {
            return 'stream';
        }

        return 'param';
    }

    /**
     * Gets the parsed param
     * @return array<string, string>
     */
    private function getParsedStream(string $block): array
    {
        preg_match('/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s', $block, $match);

        return [($match[1] ?? '') => $match[2] ?? ''];
    }

    /**
     * Gets the parsed file
     * @return array{string, UploadedFile}|null
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    private function getParsedFile(string $block): ?array
    {
        [$name, $filename, $type, $content] = $this->parseFileData($block);

        if (!$content) {
            return null;
        }

        $fs = FileSystemFactory::get();
        $tempName = tempnam(sys_get_temp_dir(), 'qt_');
        $fs->put($tempName, $content);

        $file = new UploadedFile([
            'name' => $filename,
            'type' => $type,
            'tmp_name' => $tempName,
            'error' => UPLOAD_ERR_OK,
            'size' => $fs->size($tempName),
        ]);

        register_shutdown_function(function () use ($fs, $tempName): void {
            $fs->remove($tempName);
        });

        return [$name, $file];
    }

    /**
     * Parses a file block into metadata and binary content
     * @return array{string, string, string, string}
     */
    private function parseFileData(string $block): array
    {
        $block = ltrim($block, "\r\n");

        $parts = explode("\r\n\r\n", $block, 2);

        if (count($parts) < 2) {
            return ['-unknown-', '-unknown-', ContentType::OCTET_STREAM, ''];
        }

        [$rawHeaders, $content] = $parts;

        [$name, $filename, $contentType] = $this->parseHeaders($rawHeaders);

        $content = substr($content, 0, strlen($content) - 2);

        return [
            $name,
            $filename,
            $contentType,
            $content,
        ];
    }

    /**
     * Parses a block and extracts normal form parameters
     * @return array<string, mixed>
     */
    private function getParsedParameter(string $block): array
    {
        $data = [];

        $block = trim($block);

        if (preg_match('/name="([^"]+)"\s*\r?\n\r?\n(.*)/s', $block, $match)) {
            if (preg_match('/^(.*)\[\]$/i', $match[1], $tmp)) {
                $data[$tmp[1]][] = rtrim($match[2]);
            } else {
                $data[$match[1]] = rtrim($match[2]);
            }
        }

        return $data;
    }

    /**
     * Extracts name, filename, and content type from header lines
     * @return array{string, string, string}
     */
    private function parseHeaders(string $rawHeaders): array
    {
        $name = '-unknown-';
        $filename = '-unknown-';
        $contentType = ContentType::OCTET_STREAM;

        $rawHeaders = preg_replace("/\r\n|\r|\n/", "\n", $rawHeaders) ?? $rawHeaders;
        $lines = explode("\n", $rawHeaders);

        foreach ($lines as $line) {
            if (stripos($line, 'Content-Disposition') !== false) {
                if (preg_match('/name="([^"]+)"/', $line, $match)) {
                    $name = $match[1];
                }

                if (preg_match('/filename="([^"]*)"/', $line, $match)) {
                    $filename = $match[1];
                }
            }

            if (stripos($line, 'Content-Type') !== false && preg_match('/Content-Type:\s*(.+)/i', $line, $match)) {
                $contentType = trim($match[1]);
            }
        }

        return [$name, $filename, $contentType];
    }

    /**
     * Parses array-like parameter names
     * @return array<string>|string
     */
    private function arrayParam(string $parameter): array|string
    {
        if (str_contains($parameter, '[') && preg_match('/^([^[]*)\[([^]]*)\](.*)$/', $parameter, $match)) {
            return [$match[1], $match[2]];
        }

        return $parameter;
    }
}
