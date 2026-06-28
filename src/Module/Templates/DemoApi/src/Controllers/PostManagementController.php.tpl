<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

use Quantum\Service\Exceptions\ServiceException;
use Quantum\App\Exceptions\BaseException;
use {{MODULE_NAMESPACE}}\Services\PostService;
use {{MODULE_NAMESPACE}}\DTOs\PostDTO;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Response;
use Quantum\Http\Request;

/**
 * Class PostManagementController
 * @package Modules\{{MODULE_NAME}}
 */
class PostManagementController extends BaseController
{

    /**
     * Post service
     */
    public PostService $postService;

    public function __before()
    {
        $this->postService = service(PostService::class);
    }

    /**
     * Action - get my posts
     */
    public function myPosts(): Response
    {
        $myPosts = $this->postService->getMyPosts(auth()->user()->uuid);

        return response()->json([
            'status' => 'success',
            'data' => $this->postService->transformData($myPosts->all())
        ]);
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

        $post = $this->postService->addPost($postDto);

        return response()->json([
            'status' => 'success',
            'message' => t('common.created_successfully'),
            'data' => current($this->postService->transformData([$post]))
        ]);
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
                $this->postService->deleteImage(auth()->user()->uuid . DS . $post->image);
            }

            $imageName = $this->postService->saveImage(
                $request->getFile('image'),
                auth()->user()->uuid,
                slugify($request->get('title'))
            );
        }

        $postDto = PostDTO::fromRequest($request, null, $imageName);

        $post = $this->postService->updatePost($postUuid, $postDto);

        return response()->json([
            'status' => 'success',
            'message' => t('common.updated_successfully'),
            'data' => current($this->postService->transformData([$post]))
        ]);
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

        return response()->json([
            'status' => 'success',
            'message' => t('common.deleted_successfully')
        ]);
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

        return response()->json([
            'status' => 'success',
            'message' => t('common.deleted_successfully')
        ]);
    }
}

