<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\DTOs;

use Quantum\Http\Request;

/**
 * Class CommentDTO
 * @package {{MODULE_NAMESPACE}}\DTOs
 */
class CommentDTO
{
    private string $postUuid;

    private string $userUuid;

    private string $content;

    public function __construct(
        string $postUuid,
        string $userUuid,
        string $content
    ) {
        $this->postUuid = $postUuid;
        $this->userUuid = $userUuid;
        $this->content = $content;
    }

    public static function fromRequest(Request $request, string $postUuid, string $userUuid): self
    {
        return new self($postUuid, $userUuid, trim((string)$request->get('content')));
    }

    public function getPostUuid(): string
    {
        return $this->postUuid;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function toArray(): array
    {
        return [
            'post_uuid' => $this->postUuid,
            'user_uuid' => $this->userUuid,
            'content' => $this->content,
        ];
    }
}

