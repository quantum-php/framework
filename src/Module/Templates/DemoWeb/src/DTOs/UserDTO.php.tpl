<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\DTOs;

use Quantum\Http\Request;

/**
 * Class UserDTO
 * @package {{MODULE_NAMESPACE}}\DTOs
 */
class UserDTO
{
    private string $email;

    private string $password;

    private string $firstname;

    private string $lastname;

    private string $role;

    private ?string $uuid;

    private string $image;

    public function __construct(
        string $email,
        string $password,
        string $firstname,
        string $lastname,
        string $role = '',
        ?string $uuid = null,
        string $image = ''
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->role = $role;
        $this->uuid = $uuid;
        $this->image = $image;
    }

    public static function fromRequest(Request $request, string $role, ?string $uuid = null): self
    {
        return new self(
            $request->get('email'),
            $request->get('password'),
            $request->get('firstname'),
            $request->get('lastname'),
            $role,
            $uuid
        );
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * Converts DTO to array for framework interface compatibility
     */
    public function toArray(): array
    {
        return array_filter([
            'uuid' => $this->uuid,
            'email' => $this->email,
            'password' => $this->password,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'role' => $this->role,
            'image' => $this->image,
        ], function ($value) {
            return $value !== null;
        });
    }
}

