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
        $token = $this->extractBearerToken($header);

        if ($token === null) {
            throw new RuntimeException('Authentication token is required.', 401);
        }

        $payload = $this->auth->parseToken($token);
        $user = $this->users->findById((int) $payload['sub']);

        if (!$user) {
            throw new RuntimeException('Authenticated user no longer exists.', 401);
        }

        $request->attributes['user'] = $this->users->sanitize($user);
    }

    private function extractBearerToken(string $header): ?string
    {
        foreach (explode(',', $header) as $value) {
            $value = trim($value);

            if (preg_match('/^Bearer\s+([A-Za-z0-9\-\._]+)$/i', $value, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
