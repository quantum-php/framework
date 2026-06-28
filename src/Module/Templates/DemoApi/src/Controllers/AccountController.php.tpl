<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

use Quantum\Auth\Exceptions\AuthException;
use {{MODULE_NAMESPACE}}\Services\AuthService;
use Quantum\Hasher\Hasher;
use Quantum\Http\Response;
use Quantum\Http\Request;

/**
 * Class AccountController
 * @package Modules\{{MODULE_NAME}}
 */
class AccountController extends BaseController
{
    /**
     * Auth service
     */
    public AuthService $authService;

    public function __before()
    {
        $this->authService = service(AuthService::class);
    }

    /**
     * Action - update user info
     */
    public function update(Request $request): Response
    {
        try {
            $firstname = $request->get('firstname');
            $lastname = $request->get('lastname');

            $this->authService->update('uuid', auth()->user()->uuid, [
                'firstname' => $firstname,
                'lastname' => $lastname
            ]);

            auth()->refreshUser(auth()->user()->uuid);

            return response()->json([
                'status' => self::STATUS_SUCCESS,
                'message' => t('common.updated_successfully')
            ]);
        } catch (AuthException $e) {
            return response()->json([
                'status' => self::STATUS_ERROR,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Action - update password
     */
    public function updatePassword(Request $request): Response
    {
        try {
            $hasher = new Hasher();

            $newPassword = $request->get('new_password');

            $this->authService->update('uuid', auth()->user()->uuid, [
                'password' => $hasher->hash($newPassword)
            ]);

            return response()->json([
                'status' => self::STATUS_SUCCESS,
                'message' => t('common.updated_successfully')
            ]);
        } catch (AuthException $e) {
            return response()->json([
                'status' => self::STATUS_ERROR,
                'message' => $e->getMessage()
            ]);
        }
    }
}

