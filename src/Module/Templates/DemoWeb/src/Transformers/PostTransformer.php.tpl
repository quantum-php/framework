<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Transformers;

use Quantum\Transformer\Contracts\TransformerInterface;

/**
 * Class PostTransformer
 * @package Modules\{{MODULE_NAME}}
 */
class PostTransformer implements TransformerInterface
{
    /**
     * Transforms the post data
     */
    public function transform($item): array
    {
        return [
            'uuid' => $item->uuid,
            'title' => $item->title,
            'content' => markdown_to_html($item->content, true),
            'image' => $item->image ? $item->user_directory . '/' . $item->image : null,
            'date' => date('Y/m/d H:i', strtotime($item->updated_at)),
            'author' => $item->firstname . ' ' . $item->lastname
        ];
    }
}

