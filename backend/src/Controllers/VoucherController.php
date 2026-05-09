<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\ProductRepository;
use App\Repositories\VoucherRepository;
use DateTimeImmutable;
use RuntimeException;

final class VoucherController
{
    private const MANAGER_ROLES = ['manager', 'admin'];

    public function __construct(
        private readonly VoucherRepository $vouchers,
        private readonly ProductRepository $products
    ) {
    }

    public function index(Request $request): array
    {
        $this->assertManagerAccess($request);

        return [
            'vouchers' => $this->vouchers->all(),
        ];
    }

    public function store(Request $request): array
    {
        $this->assertManagerAccess($request);
        $payload = $this->validatedPayload($request);

        if ($this->vouchers->codeExists($payload['code'])) {
            throw new RuntimeException('Voucher code already exists.', 422);
        }

        return [
            'message' => 'Voucher created successfully.',
            'voucher' => $this->vouchers->create($payload),
            'status' => 201,
        ];
    }

    public function update(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        if (!$this->vouchers->find($id)) {
            throw new RuntimeException('Voucher not found.', 404);
        }

        $payload = $this->validatedPayload($request);

        if ($this->vouchers->codeExists($payload['code'], $id)) {
            throw new RuntimeException('Voucher code already exists.', 422);
        }

        return [
            'message' => 'Voucher updated successfully.',
            'voucher' => $this->vouchers->update($id, $payload),
        ];
    }

    public function destroy(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        if (!$this->vouchers->delete($id)) {
            throw new RuntimeException('Voucher not found.', 404);
        }

        return [
            'message' => 'Voucher deleted successfully.',
        ];
    }

    private function validatedPayload(Request $request): array
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $discountPercent = (float) $request->input('discountPercent', 0);
        $scopeType = trim((string) $request->input('scopeType', 'all'));
        $categoryName = trim((string) $request->input('categoryName'));
        $expiresAt = trim((string) $request->input('expiresAt'));
        $isActive = (bool) $request->input('isActive', true);
        $productIds = $this->normalizedProductIds($request->input('productIds', []));

        if ($code === '') {
            throw new RuntimeException('Voucher code is required.', 422);
        }

        if (!preg_match('/^[A-Z0-9_-]+$/', $code)) {
            throw new RuntimeException('Voucher code may only contain letters, numbers, dashes, and underscores.', 422);
        }

        if ($discountPercent <= 0 || $discountPercent > 100) {
            throw new RuntimeException('Discount percent must be between 0 and 100.', 422);
        }

        if (!in_array($scopeType, ['all', 'category', 'products'], true)) {
            throw new RuntimeException('Voucher scope is invalid.', 422);
        }

        if ($scopeType === 'category' && $categoryName === '') {
            throw new RuntimeException('Category is required when voucher scope is category-based.', 422);
        }

        if ($scopeType === 'products' && $productIds === []) {
            throw new RuntimeException('Select at least one product for product-limited vouchers.', 422);
        }

        foreach ($productIds as $productId) {
            if (!$this->products->find($productId)) {
                throw new RuntimeException('One or more selected products were not found.', 422);
            }
        }

        $expiration = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $expiresAt)
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $expiresAt)
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i', $expiresAt);

        if (!$expiration) {
            throw new RuntimeException('Voucher expiration must be a valid date and time.', 422);
        }

        return [
            'code' => $code,
            'discountPercent' => round($discountPercent, 2),
            'scopeType' => $scopeType,
            'categoryName' => $scopeType === 'category' ? $categoryName : null,
            'productIds' => $scopeType === 'products' ? $productIds : [],
            'expiresAt' => $expiration->format('Y-m-d H:i:s'),
            'isActive' => $isActive,
        ];
    }

    private function normalizedProductIds(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            $values
        ))));

        return array_values(array_filter($ids, static fn (int $value): bool => $value > 0));
    }

    private function assertManagerAccess(Request $request): void
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('You are not allowed to manage vouchers.', 403);
        }
    }
}
