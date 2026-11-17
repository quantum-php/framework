<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

use {{MODULE_NAMESPACE}}\Services\CommentService;
use {{MODULE_NAMESPACE}}\DTOs\CommentDTO;
use Quantum\Http\Response;
use Quantum\Http\Request;

/**
 * Class CommentController
 * @package Modules\{{MODULE_NAME}}
 */
class CommentController extends BaseController
{
    public CommentService $commentService;

    public function __before()
    {
        $this->commentService = service(CommentService::class);
    }

    /**
     * Action - create comment
     */
    public function create(Request $request, string $uuid): Response
    {
        $commentDto = CommentDTO::fromRequest($request, $uuid, auth()->user()->uuid);

        $comment = $this->commentService->addComment($commentDto);

        return response()->json([
            'status' => 'success',
            'message' => t('actions.created_successfully'),
            'data' => $comment
        ]);
    }

    /**
     * Action - delete comment
     */
    public function delete(string $uuid): Response
    {
        $this->commentService->deleteComment($uuid);

        return response()->json([
            'status' => 'success',
            'message' => t('actions.deleted_successfully'),
        ]);
    }
}

