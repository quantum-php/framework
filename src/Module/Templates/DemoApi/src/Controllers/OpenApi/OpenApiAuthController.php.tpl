<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers\OpenApi;

use Quantum\Http\Request;

/**
 * Class OpenApiAuthController
 * @package Modules\Api
 */
abstract class OpenApiAuthController extends OpenApiController
{
    /**
     * Sign in action
     * @OA\Post(
     *    path="/api/signin",
     *    tags={"Authentication"},
     *    summary="Sign in action",
     *    operationId="userSignIn",
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="application/json",
     *        @OA\Schema(
     *          required={"email", "password"},
     *          @OA\Property(property="email", type="string"),
     *          @OA\Property(property="password", type="string"),
     *          example={"email": "mail@example.com", "password": "password"}
     *        )
     *      )
     *    ),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        oneOf={
     *          @OA\Schema(
     *            example={
     *              "status": "success",
     *              "tokens": {
     *                "access_token": "base64-jwt-token",
     *                "refresh_token": "refresh-token"
     *              }
     *            }
     *          ),
     *          @OA\Schema(example={"status": "success", "code": "otp_token"})
     *        }
     *      )
     *    ),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function signin(Request $request);

    /**
     * Gets the logged-in user data
     * @OA\Get(
     *    path="/api/me",
     *    tags={"User"},
     *    summary="Gets the logged-in user data",
     *    operationId="me",
     *    security={{"bearer_token": {}}},
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={"status": "success", "data": {"firstname": "Jon", "lastname": "Smit", "email": "mail@example.com"}}
     *      )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized Request"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function me();

    /**
     * Sign out action
     * @OA\Get(
     *    path="/api/signout",
     *    tags={"Authentication"},
     *    summary="Sign out action",
     *    operationId="signout",
     *    @OA\Parameter(name="refresh_token", description="Refresh token", required=true, in="header", @OA\Schema(type="string")),
     *    security={{"bearer_token": {}}},
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={
     *          "status": "success",
     *          "tokens": {
     *            "access_token": "base64-jwt-token",
     *            "refresh_token": "refresh-token"
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
    abstract public function signout();

    /**
     * Sign up action
     * @OA\Post(
     *    path="/api/signup",
     *    tags={"Authentication"},
     *    summary="Sign up action",
     *    operationId="signUpApi",
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="application/json",
     *        @OA\Schema(
     *          required={"email", "password", "firstname", "lastname"},
     *          @OA\Property(property="email", type="string"),
     *          @OA\Property(property="password", type="string"),
     *          @OA\Property(property="firstname", type="string"),
     *          @OA\Property(property="lastname", type="string"),
     *          example={"email": "mail@example.com", "password": "password", "firstname": "Jon", "lastname": "Smit"}
     *        )
     *      )
     *    ),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={"status": "success", "message": "Successfully signed up"}
     *      )
     *    ),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function signup(Request $request);

    /**
     * Activate action
     * @OA\Get(
     *    path="/api/activate/{token}",
     *    tags={"Authentication"},
     *    summary="Activate action",
     *    operationId="activateProfile",
     *    @OA\Parameter(name="token", description="Activate token", required=true, in="path", @OA\Schema(type="string")),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={"status": "success", "message": "Account activated"}
     *      )
     *    ),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function activate(Request $request);

    /**
     * Forget action
     * @OA\Post(
     *    path="/api/forget",
     *    tags={"Authentication"},
     *    summary="Forget action",
     *    operationId="forgetPassword",
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="application/json",
     *        @OA\Schema(
     *          required={"email"},
     *          @OA\Property(property="email", type="string"),
     *          example={"email": "mail@example.com"}
     *        )
     *      )
     *    ),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={"status": "success", "message": "Check your email"}
     *      )
     *    ),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function forget(Request $request);

    /**
     * Reset action
     * @OA\Post(
     *    path="/api/reset/{token}",
     *    tags={"Authentication"},
     *    summary="Reset action",
     *    operationId="resetPassword",
     *    @OA\Parameter(name="token", description="Reset token", required=true, in="path", @OA\Schema(type="string")),
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="application/json",
     *        @OA\Schema(
     *          required={"password", "repeat_password"},
     *          @OA\Property(property="password", type="string"),
     *          @OA\Property(property="repeat_password", type="string"),
     *          example={"password": "password", "repeat_password": "password"}
     *        )
     *      )
     *    ),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={
     *          "status": "success",
     *          "tokens": {
     *            "access_token": "base64-jwt-token",
     *            "refresh_token": "refresh-token"
     *          }
     *        }
     *      )
     *    ),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function reset(Request $request);

    /**
     * Verify action
     * @OA\Post(
     *    path="/api/verify",
     *    tags={"Authentication"},
     *    summary="Verify action",
     *    operationId="accountVerify",
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="application/json",
     *        @OA\Schema(
     *          required={"otp", "code"},
     *          @OA\Property(property="otp", type="integer"),
     *          @OA\Property(property="code", type="string"),
     *          example={"otp": 123456, "code": "otp_token"}
     *        )
     *      )
     *    ),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={"status": "success"}
     *      )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized Request"),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function verify(Request $request);

    /**
     * Resend action
     * @OA\Get(
     *    path="/api/resend/{code}",
     *    tags={"Authentication"},
     *    summary="Resend action",
     *    operationId="resendOTP",
     *    @OA\Parameter(name="code", description="OTP code", required=true, in="path", @OA\Schema(type="string")),
     *    @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *        example={"status": "success", "code": "otp_token"}
     *      )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized Request"),
     *    @OA\Response(response=422, description="Unprocessable Entity"),
     *    @OA\Response(response=429, description="Too Many Requests"),
     *    @OA\Response(response=500, description="Internal Server Error")
     *  )
     */
    abstract public function resend();
}



