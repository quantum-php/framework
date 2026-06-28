<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Response;

use Quantum\Http\Exceptions\HttpException;
use Quantum\App\Enums\ReservedKeys;
use Quantum\Http\Enums\ContentType;
use SimpleXMLElement;
use DOMDocument;
use Exception;

/**
 * Trait Body
 * @package Quantum\Http
 */
trait Body
{
    /**
     * Response
     * @var array<string, mixed>
     */
    private array $__response = [];

    /**
     * @var string[]
     */
    private array $formatters = [
        ContentType::HTML => 'formatHtml',
        ContentType::XML => 'formatXml',
        ContentType::JSON => 'formatJson',
        ContentType::JSONP => 'formatJsonp',
    ];

    /**
     * Checks if response contains a data by given key
     */
    public function has(string $key): bool
    {
        return isset($this->__response[$key]);
    }

    /**
     * Gets the data from response by given key
     * @return mixed
     */
    public function get(string $key, ?string $default = null)
    {
        return $this->has($key) ? $this->__response[$key] : $default;
    }

    /**
     * Sets new key/value pair into response
     * @param mixed $value
     */
    public function set(string $key, $value): self
    {
        $this->__response[$key] = $value;
        return $this;
    }

    /**
     * Gets all response parameters
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->__response;
    }

    /**
     * Deletes the element from response by given key
     */
    public function delete(string $key): void
    {
        if ($this->has($key)) {
            unset($this->__response[$key]);
        }
    }

    /**
     * Prepares the JSON response
     * @param array<string, mixed>|null $data
     */
    public function json(?array $data = null, ?int $code = null): self
    {
        $this->setContentType(ContentType::JSON);

        if (!is_null($code)) {
            $this->setStatusCode($code);
        }

        if ($data) {
            $this->__response = array_merge($this->__response, $data);
        }

        return $this;
    }

    /**
     * Prepares the JSONP response
     * @param array<string, mixed>|null $data
     */
    public function jsonp(string $callback, ?array $data = null, ?int $code = null): self
    {
        $this->setContentType(ContentType::JSONP);

        $this->callbackFunction = $callback;

        if (!is_null($code)) {
            $this->setStatusCode($code);
        }

        if ($data) {
            $this->__response = array_merge($this->__response, $data);
        }

        return $this;
    }

    /**
     * Returns response with function
     * @param array<string, mixed> $data
     */
    public function getJsonPData(array $data): string
    {
        return $this->callbackFunction . '(' . json_encode($data) . ')';
    }

    /**
     * Prepares the XML response
     * @param array<string, mixed>|null $data
     * @param string $root
     */
    public function xml(?array $data = null, $root = '<data></data>', ?int $code = null): self
    {
        $this->setContentType(ContentType::XML);

        if (!is_null($code)) {
            $this->setStatusCode($code);
        }

        $this->xmlRoot = $root;

        if ($data) {
            $this->__response = array_merge($this->__response, $data);
        }

        return $this;
    }

    /**
     * Prepares the HTML content
     */
    public function html(string $html, ?int $code = null): self
    {
        $this->setContentType(ContentType::HTML);

        if (!is_null($code)) {
            $this->setStatusCode($code);
        }

        $this->__response[ReservedKeys::RENDERED_VIEW] = $html;

        return $this;
    }

    /**
     * Gets the response content
     * @throws HttpException
     */
    public function getContent(): string
    {
        $contentType = $this->getContentType();

        if (!isset($this->formatters[$contentType])) {
            throw new HttpException("Unsupported content type: {$contentType}");
        }

        $formatterMethod = $this->formatters[$contentType];

        return $this->$formatterMethod();
    }

    /**
     * Transforms array to XML
     * @param array<string, mixed> $arr
     * @throws Exception
     */
    private function arrayToXML(array $arr): string
    {
        $simpleXML = new SimpleXMLElement($this->xmlRoot);
        $this->composeXML($arr, $simpleXML);

        $dom = new DOMDocument();
        $xml = $simpleXML->asXML();

        if ($xml === false) {
            return '';
        }

        $dom->loadXML($xml);
        $dom->formatOutput = true;
        return $dom->saveXML() ?: '';
    }

    /**
     * Compose XML
     * @param array<string, mixed> $arr
     */
    private function composeXML(array $arr, SimpleXMLElement &$simpleXML): void
    {
        foreach ($arr as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item' . $key;
            }

            $tag = $key;
            $attributes = null;

            if (str_contains($key, '@')) {
                [$tag, $attributes] = explode('@', $key);
                $attributes = json_decode($attributes);
            }

            if (is_array($value)) {
                $child = $simpleXML->addChild($tag);
                if ($attributes) {
                    foreach ($attributes as $attrKey => $attrVal) {
                        $child->addAttribute($attrKey, $attrVal);
                    }
                }

                $this->composeXML($value, $child);
            } else {
                $child = $simpleXML->addChild($tag, htmlspecialchars((string) $value));

                if ($attributes) {
                    foreach ($attributes as $attrKey => $attrVal) {
                        $child->addAttribute($attrKey, $attrVal);
                    }
                }
            }
        }
    }

    /**
     * Formats data as JSON
     */
    private function formatJson(): string
    {
        return json_encode($this->all(), JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * Formats data as XML
     * @throws Exception
     */
    private function formatXml(): string
    {
        return $this->arrayToXML($this->all());
    }

    /**
     * Formats data as HTML
     */
    private function formatHtml(): string
    {
        return $this->get(ReservedKeys::RENDERED_VIEW) ?? '';
    }

    /**
     * Formats data as JSONP
     */
    private function formatJsonp(): string
    {
        return $this->getJsonPData($this->all());
    }
}
