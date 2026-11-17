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
 * Class OpenApiPostController
 * @package Modules\Api
 */
abstract class OpenApiPostController extends OpenApiController
{
    /**
     * Get posts action
     */
    #[OA\Get(
        path: '/api/posts',
        tags: ['Posts'],
        summary: 'Get posts action',
        operationId: 'posts',
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 8)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    example: [
                        'status' => 'success',
                        'data' => [
                            [
                                'uuid' => '4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af',
                                'title' => 'Demo Post',
                                'content' => '<p>Post content</p>',
                                'image' => '5d8f.../post-image.jpg',
                                'date' => '2026/05/14 10:30',
                                'author' => 'Jon Smit',
                            ],
                        ],
                        'pagination' => [
                            'total_records' => 10,
                            'current_page' => 1,
                            'next_page' => 2,
                            'prev_page' => null,
                        ],
                    ]
                )
            ),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function posts(Request $request);

    /**
     * Get post action
     */
    #[OA\Get(
        path: '/api/post/{uuid}',
        tags: ['Posts'],
        summary: 'Get post action',
        operationId: 'post',
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Post UUID',
                required: true,
                in: 'path',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    example: [
                        'status' => 'success',
                        'data' => [
                            'uuid' => '4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af',
                            'title' => 'Demo Post',
                            'content' => '<p>Post content</p>',
                            'image' => '5d8f.../post-image.jpg',
                            'date' => '2026/05/14 10:30',
                            'author' => 'Jon Smit',
                            'comments' => [
                                [
                                    'uuid' => '40f0e8a0-bcd6-11ee-9c66-9f57d21b5b9f',
                                    'author' => [
                                        'firstname' => 'Jane',
                                        'lastname' => 'Doe',
                                        'image' => 'e31a.../avatar.png',
                                    ],
                                    'content' => 'Great post',
                                    'date' => '2026-05-14 10:35',
                                ],
                            ],
                        ],
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Not Found'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function post(string $uuid);

    /**
     * Get my posts action
     */
    #[OA\Get(
        path: '/api/my-posts',
        tags: ['Posts'],
        summary: 'Get my posts action',
        operationId: 'myPosts',
        security: [['bearer_token' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    example: [
                        'status' => 'success',
                        'data' => [
                            [
                                'uuid' => '4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af',
                                'title' => 'My Post',
                                'content' => '<p>Post content</p>',
                                'image' => '5d8f.../post-image.jpg',
                                'date' => '2026/05/14 10:30',
                                'author' => 'Jon Smit',
                            ],
                        ],
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized Request'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function myPosts();

    /**
     * Create post action
     */
    #[OA\Post(
        path: '/api/my-posts/create',
        tags: ['Posts'],
        summary: 'Create post action',
        operationId: 'create',
        security: [['bearer_token' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['title', 'content'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'content', type: 'string'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                    ]
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
                            'uuid' => '4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af',
                            'title' => 'Created Post',
                            'content' => '<p>Post content</p>',
                            'image' => '5d8f.../post-image.jpg',
                            'date' => '2026/05/14 10:30',
                            'author' => 'Jon Smit',
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
    abstract public function create(Request $request);

    /**
     * Amend post action
     */
    #[OA\Put(
        path: '/api/my-posts/amend/{uuid}',
        tags: ['Posts'],
        summary: 'Amend post action',
        operationId: 'amend',
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
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['title', 'content'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'content', type: 'string'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                    ]
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
                        'message' => 'Updated successfully',
                        'data' => [
                            'uuid' => '4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af',
                            'title' => 'Updated Post',
                            'content' => '<p>Updated content</p>',
                            'image' => '5d8f.../post-image.jpg',
                            'date' => '2026/05/14 11:00',
                            'author' => 'Jon Smit',
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
    abstract public function amend(Request $request, string $uuid);

    /**
     * Delete post action
     */
    #[OA\Delete(
        path: '/api/my-posts/delete/{uuid}',
        tags: ['Posts'],
        summary: 'Delete post action',
        operationId: 'delete',
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

    /**
     * Delete post image action
     */
    #[OA\Delete(
        path: '/api/my-posts/delete-image/{uuid}',
        tags: ['Posts'],
        summary: 'Delete post image action',
        operationId: 'deleteImage',
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
    abstract public function deleteImage(string $uuid);
}
