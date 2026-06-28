<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

use Quantum\Service\Exceptions\ServiceException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Enums\StatusCode;
use {{MODULE_NAMESPACE}}\Services\CommentService;
use {{MODULE_NAMESPACE}}\Services\PostService;
use Quantum\Http\Response;
use Quantum\Http\Request;

/**
 * Class PostController
 * @package Modules\{{MODULE_NAME}}
 */
class PostController extends BaseController
{
    /**
     * Posts per page
     */
    protected const POSTS_PER_PAGE = 8;

    /**
     * Current page
     */
    protected const CURRENT_PAGE = 1;

    /**
     * Post service
     */
    public PostService $postService;

    public function __before()
    {
        $this->postService = service(PostService::class);
    }

    /**
     * Action - get posts list
     */
    public function posts(Request $request): Response
    {
        $perPage = $request->get('per_page', (string) self::POSTS_PER_PAGE);
        $currentPage = $request->get('page', (string) self::CURRENT_PAGE);
        $search = trim((string) $request->get('q'));

        $paginatedPosts = $this->postService->getPosts($perPage, $currentPage, $search);

        return response()->json([
            'status' => 'success',
            'data' => $this->postService->transformData($paginatedPosts->data()->all()),
            'pagination' => [
                'total_records' => $paginatedPosts->total(),
                'current_page' => $paginatedPosts->currentPageNumber(),
                'next_page' => $paginatedPosts->nextPageNumber(),
                'prev_page' => $paginatedPosts->previousPageNumber(),
            ]
        ]);
    }

    /**
     * Action - get single post
     */
    public function post(?string $lang, string $postUuid): Response
    {
        $post = $this->postService->getPost($postUuid);

        if ($post->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => t('common.post_not_found')
            ], StatusCode::NOT_FOUND);
        }

        $postData = current($this->postService->transformData([$post]));

        $commentService = service(CommentService::class);

        $comments = $commentService->getCommentsByPost($postUuid);

        $commentsData = $commentService->transformData($comments->all());

        $postData['comments'] = $commentsData;

        return response()->json([
            'status' => 'success',
            'data' => $postData,
        ]);
    }
}

