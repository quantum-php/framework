<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers\OpenApi;

use Quantum\Http\Request;

/**
 * Class OpenApiAccountController
 * @package Modules\Api
 */
abstract class OpenApiAccountController extends OpenApiController
{
    /**
     * Update user info action
     * @OA\Put(
     *    path="/api/account-settings/update",
     *    tags={"Account"},
     *    summary="Update user info",
     *    operationId="updateAccount",
     *    security={{"bearer_token": {}}},
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="application/json",
     *        @OA\Schema(
     *          required={"firstname", "lastname"},
     *          @OA\Property(property="firstname", type="string"),
     *          @OA\Property(property="lastname", type="string"),
     *          example={"firstname": "Jon", "lastname": "Smit"}
     *        )
     *      )
     *    ),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={"status": "success", "message": "Updated successfully"}
     *      )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized Request"),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function update(Request $request);

    /**
     * Update password action
     * @OA\Put(
     *    path="/api/account-settings/update-password",
     *    tags={"Account"},
     *    summary="Update password",
     *    operationId="updatePassword",
     *    security={{"bearer_token": {}}},
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="application/json",
     *        @OA\Schema(
     *          required={"current_password", "new_password", "confirm_password"},
     *          @OA\Property(property="current_password", type="string"),
     *          @OA\Property(property="new_password", type="string"),
     *          @OA\Property(property="confirm_password", type="string"),
     *          example={"current_password": "oldPassword", "new_password": "newPassword", "confirm_password": "newPassword"}
     *        )
     *      )
     *    ),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={"status": "success", "message": "Updated successfully"}
     *      )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized Request"),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function updatePassword(Request $request);
}



