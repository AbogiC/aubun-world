<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use RuntimeException;

final class AuthMiddleware
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly UserRepository $users
    ) {
    }

    public function handle(Request $request): void
    {
        $header = (string) $request->header('Authorization', '');

        if (!preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            throw new RuntimeException('Authentication token is required.', 401);
        }

        $payload = $this->auth->parseToken($matches[1]);
        $user = $this->users->findById((int) $payload['sub']);

        if (!$user) {
            throw new RuntimeException('Authenticated user no longer exists.', 401);
        }

        $request->attributes['user'] = $this->users->sanitize($user);
    }
}
