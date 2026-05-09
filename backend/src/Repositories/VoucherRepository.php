<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;
use Throwable;

final class VoucherRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, code, discount_percent, scope_type, category_name, expires_at, is_active
             FROM vouchers
             ORDER BY expires_at ASC, id DESC'
        );
        $vouchers = $statement->fetchAll();

        if ($vouchers === []) {
            return [];
        }

        $voucherIds = array_map(
            static fn (array $voucher): int => (int) $voucher['id'],
            $vouchers
        );
        $productIdsByVoucher = $this->voucherProductIdsByVoucherIds($voucherIds);

        return array_map(
            fn (array $voucher): array => $this->mapVoucher(
                $voucher,
                $productIdsByVoucher[(int) $voucher['id']] ?? []
            ),
            $vouchers
        );
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, code, discount_percent, scope_type, category_name, expires_at, is_active
             FROM vouchers
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $voucher = $statement->fetch();

        if (!$voucher) {
            return null;
        }

        $productIdsByVoucher = $this->voucherProductIdsByVoucherIds([$id]);

        return $this->mapVoucher($voucher, $productIdsByVoucher[$id] ?? []);
    }

    public function findByCode(string $code): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, code, discount_percent, scope_type, category_name, expires_at, is_active
             FROM vouchers
             WHERE UPPER(code) = UPPER(:code)
             LIMIT 1'
        );
        $statement->execute(['code' => $code]);
        $voucher = $statement->fetch();

        if (!$voucher) {
            return null;
        }

        $voucherId = (int) $voucher['id'];
        $productIdsByVoucher = $this->voucherProductIdsByVoucherIds([$voucherId]);

        return $this->mapVoucher($voucher, $productIdsByVoucher[$voucherId] ?? []);
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM vouchers WHERE UPPER(code) = UPPER(:code)';
        $params = ['code' => $code];

        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetchColumn();
    }

    public function create(array $payload): array
    {
        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare(
                'INSERT INTO vouchers (
                    code, discount_percent, scope_type, category_name, expires_at, is_active, created_at, updated_at
                 ) VALUES (
                    :code, :discount_percent, :scope_type, :category_name, :expires_at, :is_active, NOW(), NOW()
                 )'
            );
            $statement->execute($this->persistedVoucher($payload));

            $voucherId = (int) $this->pdo->lastInsertId();
            $this->replaceVoucherProducts($voucherId, $payload['productIds'] ?? []);
            $this->pdo->commit();

            return $this->find($voucherId)
                ?? throw new RuntimeException('Unable to load saved voucher.', 500);
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function update(int $id, array $payload): ?array
    {
        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare(
                'UPDATE vouchers SET
                    code = :code,
                    discount_percent = :discount_percent,
                    scope_type = :scope_type,
                    category_name = :category_name,
                    expires_at = :expires_at,
                    is_active = :is_active,
                    updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                ...$this->persistedVoucher($payload),
            ]);

            $this->replaceVoucherProducts($id, $payload['productIds'] ?? []);
            $this->pdo->commit();

            return $this->find($id);
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM vouchers WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function persistedVoucher(array $payload): array
    {
        return [
            'code' => $payload['code'],
            'discount_percent' => $payload['discountPercent'],
            'scope_type' => $payload['scopeType'],
            'category_name' => $payload['categoryName'],
            'expires_at' => $payload['expiresAt'],
            'is_active' => $payload['isActive'] ? 1 : 0,
        ];
    }

    private function replaceVoucherProducts(int $voucherId, array $productIds): void
    {
        $delete = $this->pdo->prepare('DELETE FROM voucher_products WHERE voucher_id = :voucher_id');
        $delete->execute(['voucher_id' => $voucherId]);

        if ($productIds === []) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO voucher_products (voucher_id, product_id, created_at, updated_at)
             VALUES (:voucher_id, :product_id, NOW(), NOW())'
        );

        foreach ($productIds as $productId) {
            $insert->execute([
                'voucher_id' => $voucherId,
                'product_id' => $productId,
            ]);
        }
    }

    private function voucherProductIdsByVoucherIds(array $voucherIds): array
    {
        if ($voucherIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($voucherIds), '?'));
        $statement = $this->pdo->prepare(
            "SELECT voucher_id, product_id
             FROM voucher_products
             WHERE voucher_id IN ($placeholders)
             ORDER BY product_id ASC"
        );
        $statement->execute($voucherIds);

        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $voucherId = (int) $row['voucher_id'];
            $grouped[$voucherId] ??= [];
            $grouped[$voucherId][] = (int) $row['product_id'];
        }

        return $grouped;
    }

    private function mapVoucher(array $voucher, array $productIds): array
    {
        return [
            'id' => (int) $voucher['id'],
            'code' => $voucher['code'],
            'discountPercent' => (float) $voucher['discount_percent'],
            'scopeType' => $voucher['scope_type'],
            'categoryName' => $voucher['category_name'],
            'productIds' => $productIds,
            'expiresAt' => $voucher['expires_at'],
            'isActive' => (bool) $voucher['is_active'],
        ];
    }
}
