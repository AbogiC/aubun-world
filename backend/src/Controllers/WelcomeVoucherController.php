<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\WelcomeVoucherRepository;
use RuntimeException;

final class WelcomeVoucherController
{
    private const MANAGER_ROLES = ['manager', 'admin'];

    public function __construct(private readonly WelcomeVoucherRepository $welcomeVouchers)
    {
    }

    public function getSettings(Request $request): array
    {
        $this->assertManagerAccess($request);

        return [
            'settings' => $this->welcomeVouchers->getSettings(),
        ];
    }

    public function updateSettings(Request $request): array
    {
        $this->assertManagerAccess($request);

        $discountPercent = (float) $request->input('discountPercent', 0);
        $validityDays = (int) $request->input('validityDays', 0);
        // Checkbox may send 0/1, "true"/"false", or boolean.
        $rawEnabled = $request->input('isEnabled', true);
        $isEnabled = filter_var($rawEnabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $rawEnabled;

        return [
            'message' => 'Welcome voucher settings saved.',
            'settings' => $this->welcomeVouchers->updateSettings($discountPercent, $validityDays, $isEnabled),
        ];
    }

    public function myVouchers(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];

        return [
            'vouchers' => $this->welcomeVouchers->allByUserId($userId),
        ];
    }

    private function assertManagerAccess(Request $request): void
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('You are not allowed to manage voucher settings.', 403);
        }
    }
}
