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
        parent::__before();
    }

    /**
     * Action - create comment
     */
    public function create(Request $request, string $uuid): Response
    {
        $commentDto = CommentDTO::fromRequest($request, $uuid, auth()->user()->uuid);

        $this->commentService->addComment($commentDto);

        session()->setFlash('success', t('comments.added'));
        return back();
    }

    /**
     * Action - delete comment
     */
    public function delete(string $uuid): Response
    {
        $this->commentService->deleteComment($uuid);

        session()->setFlash('success', t('comments.deleted'));
        return back();
    }
}

