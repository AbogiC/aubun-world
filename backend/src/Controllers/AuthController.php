<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use RuntimeException;

final class AuthController
{
    private const ALLOWED_ROLES = ['customer', 'manager', 'admin'];

    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthService $auth
    ) {
    }

    public function register(Request $request): array
    {
        $name = trim((string) $request->input('name'));
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $role = strtolower(trim((string) ($request->input('role') ?? 'customer')));

        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException('Name, email, and password are required.', 422);
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new RuntimeException('Role must be one of: customer, manager, admin.', 422);
        }

        if ($this->users->findByEmail($email)) {
            throw new RuntimeException('Email address is already registered.', 409);
        }

        $user = $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), $role);
        $token = $this->auth->issueToken((int) $user['id'], $user['email']);

        return [
            'message' => 'Account created successfully.',
            'token' => $token,
            'user' => $this->users->sanitize($user),
        ];
    }

    public function login(Request $request): array
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $user = $this->users->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new RuntimeException('Invalid credentials.', 401);
        }

        return [
            'message' => 'Login successful.',
            'token' => $this->auth->issueToken((int) $user['id'], $user['email']),
            'user' => $this->users->sanitize($user),
        ];
    }

    public function me(Request $request): array
    {
        return [
            'user' => $request->attribute('user'),
        ];
    }
}
