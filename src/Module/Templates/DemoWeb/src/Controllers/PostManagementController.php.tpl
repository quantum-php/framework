<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

use {{MODULE_NAMESPACE}}\Services\PostService;
use {{MODULE_NAMESPACE}}\DTOs\PostDTO;
use Quantum\Http\Response;
use Quantum\Http\Request;

/**
 * Class PostManagementController
 * @package Modules\{{MODULE_NAME}}
 */
class PostManagementController extends BaseController
{
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
     * Action - get my posts
     */
    public function myPosts(Request $request): Response
    {
        $myPosts = $this->postService->getMyPosts(auth()->user()->uuid);

        $this->view->setParams([
            'title' => t('common.my_posts') . ' | ' . config()->get('app.name'),
            'posts' => $this->postService->transformData($myPosts->all())
        ]);

        return response()->html($this->view->render('post/my-posts'));
    }

    /**
     * Action - display form for creating a post
     */
    public function createFrom(Request $request): Response
    {
        $ref = $request->get('ref', 'posts');

        $this->view->setParams([
            'title' => t('common.new_post') . ' | ' . config()->get('app.name'),
            'referer' => $ref
        ]);

        return response()->html($this->view->render('post/form'));
    }

    /**
     * Action - create post
     */
    public function create(Request $request): Response
    {
        $imageName = '';

        if ($request->hasFile('image')) {
            $imageName = $this->postService->saveImage(
                $request->getFile('image'),
                auth()->user()->uuid,
                slugify($request->get('title'))
            );
        }

        $postDto = PostDTO::fromRequest($request, auth()->user()->uuid, $imageName);

        $this->postService->addPost($postDto);

        return redirect(base_url(true) . '/' . current_lang() . '/my-posts');
    }

    /**
     * Action - display form for amend the post
     */
    public function amendForm(Request $request, ?string $lang, string $postUuid): Response
    {
        $ref = $request->get('ref', 'posts');

        $post = $this->postService->getPost($postUuid);

        $this->view->setParams([
            'title' => $post->title . ' | ' . config()->get('app.name'),
            'post' => $post->asArray(),
            'referer' => nav_ref_decode($ref)
        ]);

        return response()->html($this->view->render('post/form'));
    }

    /**
     * Action - amend post
     */
    public function amend(Request $request, ?string $lang, string $postUuid): Response
    {
        $post = $this->postService->getPost($postUuid);

        $imageName = null;

        if ($request->hasFile('image')) {
            if ($post->image) {
                $this->postService->deleteImage(auth()->user()->uuid . DS .  $post->image);
            }

            $imageName = $this->postService->saveImage(
                $request->getFile('image'),
                auth()->user()->uuid,
                slugify($request->get('title'))
            );
        }

        $postDto = PostDTO::fromRequest($request, null, $imageName);

        $this->postService->updatePost($postUuid, $postDto);

        return redirect(base_url(true) . '/' . current_lang() . '/my-posts');
    }

    /**
     * Action - delete post
     */
    public function delete(?string $lang, string $postUuid): Response
    {
        $post = $this->postService->getPost($postUuid);

        if ($post->image) {
            $this->postService->deleteImage(auth()->user()->uuid . DS . $post->image);
        }

        $this->postService->deletePost($postUuid);

        return redirect(base_url(true) . '/' . current_lang() . '/my-posts');
    }

    /**
     * Action - delete image of the post
     */
    public function deleteImage(?string $lang, string $postUuid): Response
    {
        $post = $this->postService->getPost($postUuid);

        if ($post->image) {
            $this->postService->deleteImage(auth()->user()->uuid . DS . $post->image);
        }

        $postDto = new PostDTO(
            $post->title,
            $post->content,
            null,
            ''
        );

        $this->postService->updatePost($postUuid, $postDto);

        return redirect(base_url(true) . '/' . current_lang() . '/my-posts');
    }
}

