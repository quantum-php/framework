<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers\OpenApi;

use OpenApi\Attributes as OA;
use Quantum\Http\Request;

/**
 * Class OpenApiCommentController
 * @package Modules\Api
 */
abstract class OpenApiCommentController extends OpenApiController
{
    /**
     * Create comment action
     */
    #[OA\Post(
        path: '/api/comments/create/{uuid}',
        tags: ['Comments'],
        summary: 'Create comment action',
        operationId: 'createComment',
        security: [['bearer_token' => []]],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Post UUID',
                required: true,
                in: 'path',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['content'],
                    properties: [new OA\Property(property: 'content', type: 'string')],
                    example: ['content' => 'Great post']
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    example: [
                        'status' => 'success',
                        'message' => 'Created successfully',
                        'data' => [
                            'uuid' => '40f0e8a0-bcd6-11ee-9c66-9f57d21b5b9f',
                            'post_uuid' => '4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af',
                            'user_uuid' => 'e31a9f20-bcd5-11ee-8fe4-a77a76ad48c2',
                            'content' => 'Great post',
                        ],
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized Request'),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function create(Request $request, string $uuid);

    /**
     * Delete comment action
     */
    #[OA\Delete(
        path: '/api/comments/delete/{uuid}',
        tags: ['Comments'],
        summary: 'Delete comment action',
        operationId: 'deleteComment',
        security: [['bearer_token' => []]],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Comment UUID',
                required: true,
                in: 'path',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(example: ['status' => 'success', 'message' => 'Deleted successfully'])
            ),
            new OA\Response(response: 401, description: 'Unauthorized Request'),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function delete(string $uuid);
}
