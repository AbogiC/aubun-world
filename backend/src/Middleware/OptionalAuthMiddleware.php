<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Repositories\UserRepository;
use App\Services\AuthService;

/**
 * Like AuthMiddleware, but guests are allowed through.
 * If a valid Bearer token is present, `user` attribute is populated;
 * otherwise the request continues as a guest (no `user` attribute).
 * Used by checkout / PayPal routes that serve both guests and customers.
 */
final class OptionalAuthMiddleware
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
            return;
        }

        try {
            $payload = $this->auth->parseToken($token);
            $user = $this->users->findById((int) ($payload['sub'] ?? 0));

            if ($user) {
                $request->attributes['user'] = $this->users->sanitize($user);
            }
        } catch (\Throwable) {
            // Invalid/expired token on a public route -> treat as guest.
        }
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
