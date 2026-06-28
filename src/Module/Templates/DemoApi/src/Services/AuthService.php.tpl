<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Services;

use Quantum\Auth\Contracts\AuthServiceInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Model\ModelCollection;
use Quantum\Auth\User as AuthUser;
use Quantum\Service\Service;
use {{MODULE_NAMESPACE}}\Models\User;

/**
 * Class AuthService
 * @package Modules\{{MODULE_NAME}}
 */
class AuthService extends Service implements AuthServiceInterface
{
    private User $model;

    /**
     * @throws ModelException
     */
    public function __construct()
    {
        $this->model = model(User::class);
    }

    /**
     * Get users
     * @throws BaseException
     */
    public function getAll(): ModelCollection
    {
        return $this->model->get();
    }

    /**
     * Get user
     * @throws BaseException
     */
    public function getUserByUuid(string $uuid): ?User
    {
        return $this->model->findOneBy('uuid', $uuid);
    }

    /**
     * Get user
     * @throws BaseException
     */
    public function get(string $field, $value): ?AuthUser
    {
        $user = $this->model->findOneBy($field, $value);

        if ($user->isEmpty()) {
            return null;
        }

        return (new AuthUser())->setData($user->asArray());
    }

    /**
     * Add user
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function add(array $data): AuthUser
    {
        $data['uuid'] = $data['uuid'] ?? uuid_ordered();

        $this->createUserDirectory($data['uuid']);

        $user = $this->model->create();
        $user->fill($data);
        $user->save();

        return (new AuthUser())->setData($data);
    }

    /**
     * Update user
     * @throws BaseException
     * @throws ModelException
     */
    public function update(string $field, ?string $value, array $data): ?AuthUser
    {
        $user = $this->model->findOneBy($field, $value);

        if ($user->isEmpty()) {
            return null;
        }

        unset($data['id']);

        $user->fill($data);
        $user->save();

        return (new AuthUser())->setData($this->model->findOneBy($field, $value)->asArray());
    }

    /**
     * Delete users table
     */
    public function deleteAllUsers()
    {
        $this->model->truncate();
    }

    /**
     * User Schema
     */
    public function userSchema(): array
    {
        return [
            'id' => ['name' => 'id', 'visible' => true],
            'uuid' => ['name' => 'uuid', 'visible' => true],
            'firstname' => ['name' => 'firstname', 'visible' => true],
            'lastname' => ['name' => 'lastname', 'visible' => true],
            'role' => ['name' => 'role', 'visible' => true],
            'username' => ['name' => 'email', 'visible' => true],
            'password' => ['name' => 'password', 'visible' => false],
            'image' => ['name' => 'image', 'visible' => true],
            'activationToken' => ['name' => 'activation_token', 'visible' => false],
            'rememberToken' => ['name' => 'remember_token', 'visible' => false],
            'resetToken' => ['name' => 'reset_token', 'visible' => false],
            'accessToken' => ['name' => 'access_token', 'visible' => false],
            'refreshToken' => ['name' => 'refresh_token', 'visible' => false],
            'otp' => ['name' => 'otp', 'visible' => false],
            'otpExpiry' => ['name' => 'otp_expires', 'visible' => false],
            'otpToken' => ['name' => 'otp_token', 'visible' => false],
        ];
    }

    /**
     * Creates user directory
     * @throws DiException
     * @throws ReflectionException
     * @throws BaseException
     * @throws ConfigException
     */
    private function createUserDirectory(string $uuid)
    {
        $userDirectory = uploads_dir() . DS . $uuid;

        if(!fs()->isDirectory($userDirectory)) {
            fs()->makeDirectory($userDirectory);
        }
    }
}

