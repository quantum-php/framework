<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Transformers;

use Quantum\Transformer\Contracts\TransformerInterface;

/**
 * Class CommentTransformer
 * @package Modules\{{MODULE_NAME}}
 */
class CommentTransformer implements TransformerInterface
{
    public function transform($item): array
    {
        return [
            'uuid' => $item->uuid,
            'author' => [
                'firstname' => $item->firstname,
                'lastname' => $item->lastname,
                'image' => $item->user_directory . '/' . $item->image,
            ],
            'content' => $item->content,
            'date' => date('Y-m-d H:i', strtotime($item->created_at)),
        ];
    }
}

