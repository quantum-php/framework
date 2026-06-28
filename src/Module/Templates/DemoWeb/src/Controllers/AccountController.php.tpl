<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

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
     * Main layout
     */
    const LAYOUT = 'layouts/main';

    /**
     * Account service
     */
    public AuthService $authService;

    public function __before()
    {
        $this->authService = service(AuthService::class);
        parent::__before();
    }

    /**
     * Action - show user info
     */
    public function form(): Response
    {
        $this->view->setParams([
            'title' => t('common.account_settings') . ' | ' . config()->get('app.name'),
        ]);

        return response()->html($this->view->render('account/form'));
    }

    /**
     * Action - update user info
     */
    public function update(Request $request): Response
    {
        $firstname = $request->get('firstname');
        $lastname = $request->get('lastname');

        $user = $this->authService->update('uuid', auth()->user()->uuid, [
            'firstname' => $firstname,
            'lastname' => $lastname
        ]);

        auth()->refreshUser(auth()->user()->uuid);

        return redirect(base_url(true) . '/' . current_lang() . '/account-settings#account_profile');
    }

    /**
     * Action - update password
     */
    public function updatePassword(Request $request): Response
    {
        $hasher = new Hasher();

        $newPassword = $request->get('new_password', null);

        $this->authService->update('uuid', auth()->user()->uuid, [
            'password' => $hasher->hash($newPassword)
        ]);

        return redirect(base_url(true) . '/' . current_lang() . '/account-settings#account_password');
    }
}

