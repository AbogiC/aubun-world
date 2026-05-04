<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use RuntimeException;

final class RoleMiddleware
{
    /**
     * @param array<int, string> $allowedRoles
     */
    public function __construct(private readonly array $allowedRoles)
    {
    }

    public function handle(Request $request): void
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, $this->allowedRoles, true)) {
            throw new RuntimeException('You are not allowed to access this resource.', 403);
        }
    }
}
