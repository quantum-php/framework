<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Services;

use {{MODULE_NAMESPACE}}\Transformers\CommentTransformer;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use {{MODULE_NAMESPACE}}\DTOs\CommentDTO;
use Quantum\Service\Service;
use {{MODULE_NAMESPACE}}\Models\Comment;
use {{MODULE_NAMESPACE}}\Models\User;

/**
 * Class CommentService
 * @package Modules\{{MODULE_NAME}}
 */
class CommentService extends Service
{
    private Comment $model;

    private CommentTransformer $transformer;

    /**
     * @throws ModelException
     */
    public function __construct(CommentTransformer $transformer)
    {
        $this->model = model(Comment::class);
        $this->transformer = $transformer;
    }

    /**
     * Get comments by post
     * @throws BaseException
     * @throws ModelException
     */
    public function getCommentsByPost(string $postUuid)
    {
        return $this->model
            ->joinTo(model(User::class))
            ->criteria('post_uuid', '=', $postUuid)
            ->select(
                'comments.uuid',
                'comments.content',
                'comments.created_at',
                ['users.firstname' => 'firstname'],
                ['users.lastname' => 'lastname'],
                ['users.image' => 'image'],
                ['users.uuid' => 'user_directory']
            )
            ->orderBy('created_at', 'asc')
            ->get();
    }


    /**
     * Get comment
     * @throws BaseException
     */
    public function getComment(string $uuid): Comment
    {
        return $this->model->criteria('uuid', '=', $uuid)->first();
    }

    /**
     * Add a new comment
     * @throws ModelException
     */
    public function addComment(CommentDTO $commentDto): array
    {
        $uuid = uuid_ordered();

        $comment = $this->model->create();
        $comment->fill(array_merge(['uuid' => $uuid], $commentDto->toArray()));
        $comment->save();

        return $comment->asArray();
    }

    /**
     * Delete a comment
     * @throws BaseException
     * @throws ModelException
     */
    public function deleteComment(string $uuid): bool
    {
        return $this->model->findOneBy('uuid', $uuid)->delete();
    }

    /**
     * Delete all comments
     * @throws ModelException
     */
    public function deleteAllComments()
    {
        $this->model->truncate();
    }

    /**
     * Transform data
     */
    public function transformData(array $comments): array
    {
        return transform($comments, $this->transformer);
    }
}

