<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\UserRepository;
use RuntimeException;

final class UserController
{
    private const MANAGER_ROLES = ['manager', 'admin'];
    private const ALLOWED_ROLES = ['customer', 'manager', 'admin'];

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function index(Request $request): array
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('Forbidden.', 403);
        }

        $users = $this->users->all();

        return [
            'users' => array_map(fn (array $user): array => $this->users->sanitize($user), $users),
        ];
    }

    public function update(Request $request): array
    {
        $actor = $request->attribute('user');
        $actorRole = (string) ($actor['role'] ?? '');

        if (!in_array($actorRole, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('Forbidden.', 403);
        }

        $userId = (int) $request->attribute('id');
        $user = $this->users->findById($userId);

        if (!$user) {
            throw new RuntimeException('User not found.', 404);
        }

        $requestedRole = $request->input('role');
        if ($requestedRole !== null && !in_array(strtolower((string) $requestedRole), self::ALLOWED_ROLES, true)) {
            throw new RuntimeException('Role must be one of: customer, manager, admin.', 422);
        }

        $requestedIsActive = $request->input('isActive');
        if ($requestedIsActive !== null && !is_bool($requestedIsActive) && !in_array((string) $requestedIsActive, ['0', '1', 'true', 'false'], true)) {
            throw new RuntimeException('isActive must be a boolean value.', 422);
        }

        $role = $requestedRole !== null ? strtolower((string) $requestedRole) : null;
        $isActive = $requestedIsActive !== null ? filter_var($requestedIsActive, FILTER_VALIDATE_BOOLEAN) : null;

        $currentUserId = (int) ($actor['id'] ?? 0);
        if ($currentUserId === $userId && $role !== null && $role !== 'admin') {
            throw new RuntimeException('You cannot remove your own admin access.', 403);
        }

        if ($currentUserId === $userId && $isActive !== null && $isActive === false) {
            throw new RuntimeException('You cannot deactivate your own account.', 403);
        }

        $updatedUser = $this->users->updateUser($userId, $role, $isActive);

        return [
            'message' => 'User updated successfully.',
            'user' => $this->users->sanitize($updatedUser),
        ];
    }
}
