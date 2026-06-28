<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers\OpenApi;

use Quantum\Http\Request;

/**
 * Class OpenApiCommentController
 * @package Modules\Api
 */
abstract class OpenApiCommentController extends OpenApiController
{
    /**
     * Create comment action
     * @OA\Post(
     *    path="/api/comments/create/{uuid}",
     *    tags={"Comments"},
     *    summary="Create comment action",
     *    operationId="createComment",
     *    security={{"bearer_token": {}}},
     *    @OA\Parameter(name="uuid", description="Post UUID", required=true, in="path", @OA\Schema(type="string")),
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="application/json",
     *        @OA\Schema(
     *          required={"content"},
     *          @OA\Property(property="content", type="string"),
     *          example={"content": "Great post"}
     *        )
     *      )
     *    ),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={
     *          "status": "success",
     *          "message": "Created successfully",
     *          "data": {
     *            "uuid": "40f0e8a0-bcd6-11ee-9c66-9f57d21b5b9f",
     *            "post_uuid": "4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af",
     *            "user_uuid": "e31a9f20-bcd5-11ee-8fe4-a77a76ad48c2",
     *            "content": "Great post"
     *          }
     *        }
     *      )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized Request"),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function create(Request $request, ?string $lang, string $uuid);

    /**
     * Delete comment action
     * @OA\Delete(
     *    path="/api/comments/delete/{uuid}",
     *    tags={"Comments"},
     *    summary="Delete comment action",
     *    operationId="deleteComment",
     *    security={{"bearer_token": {}}},
     *    @OA\Parameter(name="uuid", description="Comment UUID", required=true, in="path", @OA\Schema(type="string")),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={"status": "success", "message": "Deleted successfully"}
     *      )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized Request"),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function delete(?string $lang, string $uuid);
}




