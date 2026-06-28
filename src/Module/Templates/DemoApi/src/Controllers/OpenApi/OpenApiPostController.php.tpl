<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers\OpenApi;

use Quantum\Http\Request;

/**
 * Class OpenApiPostController
 * @package Modules\Api
 */
abstract class OpenApiPostController extends OpenApiController
{
    /**
     * Get posts action
     * @OA\Get(
     *    path="/api/posts",
     *    tags={"Posts"},
     *    summary="Get posts action",
     *    operationId="posts",
     *    @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=8)),
     *    @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     *    @OA\Parameter(name="q", in="query", required=false, @OA\Schema(type="string")),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={
     *          "status": "success",
     *          "data": {
     *            {
     *              "uuid": "4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af",
     *              "title": "Demo Post",
     *              "content": "<p>Post content</p>",
     *              "image": "5d8f.../post-image.jpg",
     *              "date": "2026/05/14 10:30",
     *              "author": "Jon Smit"
     *            }
     *          },
     *          "pagination": {
     *            "total_records": 10,
     *            "current_page": 1,
     *            "next_page": 2,
     *            "prev_page": null
     *          }
     *        }
     *      )
     *    ),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function posts(Request $request);

    /**
     * Get post action
     * @OA\Get(
     *    path="/api/post/{uuid}",
     *    tags={"Posts"},
     *    summary="Get post action",
     *    operationId="post",
     *    @OA\Parameter(name="uuid", description="Post UUID", required=true, in="path", @OA\Schema(type="string")),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={
     *          "status": "success",
     *          "data": {
     *            "uuid": "4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af",
     *            "title": "Demo Post",
     *            "content": "<p>Post content</p>",
     *            "image": "5d8f.../post-image.jpg",
     *            "date": "2026/05/14 10:30",
     *            "author": "Jon Smit",
     *            "comments": {
     *              {
     *                "uuid": "40f0e8a0-bcd6-11ee-9c66-9f57d21b5b9f",
     *                "author": {
     *                  "firstname": "Jane",
     *                  "lastname": "Doe",
     *                  "image": "e31a.../avatar.png"
     *                },
     *                "content": "Great post",
     *                "date": "2026-05-14 10:35"
     *              }
     *            }
     *          }
     *        }
     *      )
     *    ),
     *    @OA\Response(response=404, description="Not Found"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function post(?string $lang, string $uuid);

    /**
     * Get my posts action
     * @OA\Get(
     *    path="/api/my-posts",
     *    tags={"Posts"},
     *    summary="Get my posts action",
     *    operationId="myPosts",
     *    security={{"bearer_token": {}}},
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={
     *          "status": "success",
     *          "data": {
     *            {
     *              "uuid": "4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af",
     *              "title": "My Post",
     *              "content": "<p>Post content</p>",
     *              "image": "5d8f.../post-image.jpg",
     *              "date": "2026/05/14 10:30",
     *              "author": "Jon Smit"
     *            }
     *          }
     *        }
     *      )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized Request"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function myPosts();

    /**
     * Create post action
     * @OA\Post(
     *    path="/api/my-posts/create",
     *    tags={"Posts"},
     *    summary="Create post action",
     *    operationId="create",
     *    security={{"bearer_token": {}}},
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="multipart/form-data",
     *        @OA\Schema(
     *          type="object",
     *          required={"title", "content"},
     *          @OA\Property(property="title", type="string"),
     *          @OA\Property(property="content", type="string"),
     *          @OA\Property(property="image", type="string", format="binary")
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
     *            "uuid": "4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af",
     *            "title": "Created Post",
     *            "content": "<p>Post content</p>",
     *            "image": "5d8f.../post-image.jpg",
     *            "date": "2026/05/14 10:30",
     *            "author": "Jon Smit"
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
    abstract public function create(Request $request);

    /**
     * Amend post action
     * @OA\Put(
     *    path="/api/my-posts/amend/{uuid}",
     *    tags={"Posts"},
     *    summary="Amend post action",
     *    operationId="amend",
     *    security={{"bearer_token": {}}},
     *    @OA\Parameter(name="uuid", description="Post UUID", required=true, in="path", @OA\Schema(type="string")),
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="multipart/form-data",
     *        @OA\Schema(
     *          type="object",
     *          required={"title", "content"},
     *          @OA\Property(property="title", type="string"),
     *          @OA\Property(property="content", type="string"),
     *          @OA\Property(property="image", type="string", format="binary")
     *        )
     *      )
     *    ),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={
     *          "status": "success",
     *          "message": "Updated successfully",
     *          "data": {
     *            "uuid": "4e9b8f47-bcd5-11ee-a0f2-fb642f7f26af",
     *            "title": "Updated Post",
     *            "content": "<p>Updated content</p>",
     *            "image": "5d8f.../post-image.jpg",
     *            "date": "2026/05/14 11:00",
     *            "author": "Jon Smit"
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
    abstract public function amend(Request $request, ?string $lang, string $uuid);

    /**
     * Delete post action
     * @OA\Delete(
     *    path="/api/my-posts/delete/{uuid}",
     *    tags={"Posts"},
     *    summary="Delete post action",
     *    operationId="delete",
     *    security={{"bearer_token": {}}},
     *    @OA\Parameter(name="uuid", description="Post UUID", required=true, in="path", @OA\Schema(type="string")),
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

    /**
     * Delete post image action
     * @OA\Delete(
     *    path="/api/my-posts/delete-image/{uuid}",
     *    tags={"Posts"},
     *    summary="Delete post image action",
     *    operationId="deleteImage",
     *    security={{"bearer_token": {}}},
     *    @OA\Parameter(name="uuid", description="Post UUID", required=true, in="path", @OA\Schema(type="string")),
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
    abstract public function deleteImage(?string $lang, string $uuid);
}



