<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Models;

use Quantum\Model\Traits\HasTimestamps;
use Quantum\Model\DbModel;

/**
 * Class User
 * @package Modules\{{MODULE_NAME}}
 */
class User extends DbModel
{

    use HasTimestamps;

    /**
     * ID column of table
     * @var string
     */
    public string $idColumn = 'id';

    /**
     * The table name
     * @var string
     */
    public string $table = 'users';

    /**
     * Fillable properties
     * @var array
     */
    public array $fillable = [
        'uuid',
        'firstname',
        'lastname',
        'role',
        'email',
        'password',
        'image',
        'activation_token',
        'remember_token',
        'reset_token',
        'access_token',
        'refresh_token',
        'otp',
        'otp_expires',
        'otp_token',
    ];
}

