<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

use {{MODULE_NAMESPACE}}\Services\CommentService;
use {{MODULE_NAMESPACE}}\Services\PostService;
use Quantum\Http\Enums\StatusCode;
use Quantum\View\RawParam;
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
     * Main layout
     */
    protected const LAYOUT = 'layouts/main';

    /**
     * Post service
     */
    public PostService $postService;

    public function __before()
    {
        $this->postService = service(PostService::class);
        parent::__before();
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

        $this->view->setParams([
            'title' => t('common.posts') . ' | ' . config()->get('app.name'),
            'posts' => $this->postService->transformData($paginatedPosts->data()->all()),
            'pagination' => $paginatedPosts,
            'referer' => nav_ref_encode(request()->getQuery())
        ]);

        return response()->html($this->view->render('post/post'));
    }

    /**
     * Action - get single post
     */
    public function post(Request $request, ?string $lang, string $postUuid): Response
    {
        $ref = $request->get('ref', 'posts');

        $post = $this->postService->getPost($postUuid);

        if ($post->isEmpty()) {
            return response()->html(partial('errors/404'), StatusCode::NOT_FOUND);
        }

        $commentService = service(CommentService::class);

        $comments = $commentService->getCommentsByPost($postUuid);

        $comments = $commentService->transformData($comments->all());

        $this->view->setParams([
            'title' => $post->title . ' | ' . config()->get('app.name'),
            'post' => new RawParam(current($this->postService->transformData([$post]))),
            'comments' => $comments,
            'referer' => nav_ref_decode($ref),
        ]);

        return response()->html($this->view->render('post/single'));
    }
}

