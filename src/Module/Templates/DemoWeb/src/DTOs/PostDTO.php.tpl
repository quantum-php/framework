<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\DTOs;

use Quantum\Http\Request;

/**
 * Class PostDTO
 * @package {{MODULE_NAMESPACE}}\DTOs
 */
class PostDTO
{
    private string $title;

    private string $content;

    private ?string $userUuid;

    private ?string $image;

    public function __construct(
        string $title,
        string $content,
        ?string $userUuid = null,
        ?string $image = null
    ) {
        $this->title = $title;
        $this->content = $content;
        $this->userUuid = $userUuid;
        $this->image = $image;
    }

    public static function fromRequest(Request $request, ?string $userUuid = null, ?string $image = null): self
    {
        return new self(
            $request->get('title', null, true),
            $request->get('content', null, true),
            $userUuid,
            $image
        );
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getUserUuid(): ?string
    {
        return $this->userUuid;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function toArray(): array
    {
        return array_filter([
            'user_uuid' => $this->userUuid,
            'title' => $this->title,
            'content' => $this->content,
            'image' => $this->image,
        ], function ($value) {
            return $value !== null;
        });
    }
}

