<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;

final class WelcomeVoucherRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function ensureSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS welcome_voucher_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                discount_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
                validity_days INT UNSIGNED NOT NULL DEFAULT 30,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS user_vouchers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL UNIQUE,
                voucher_id INT UNSIGNED NOT NULL UNIQUE,
                used_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_user_vouchers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_user_vouchers_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE
            )'
        );

        $statement = $this->pdo->query('SELECT id FROM welcome_voucher_settings WHERE id = 1 LIMIT 1');
        $exists = $statement ? $statement->fetchColumn() : false;

        if (!$exists) {
            $insert = $this->pdo->prepare(
                'INSERT INTO welcome_voucher_settings (id, discount_percent, validity_days, is_enabled, created_at, updated_at)
                 VALUES (1, 10.00, 30, 1, NOW(), NOW())'
            );
            $insert->execute();
        }
    }

    public function getSettings(): array
    {
        $this->ensureSchema();

        $statement = $this->pdo->query('SELECT * FROM welcome_voucher_settings WHERE id = 1 LIMIT 1');
        $row = $statement ? $statement->fetch() : false;

        if (!$row) {
            throw new RuntimeException('Welcome voucher settings not found.', 500);
        }

        return $this->mapSettings($row);
    }

    public function updateSettings(float $discountPercent, int $validityDays, bool $isEnabled): array
    {
        $this->ensureSchema();

        if ($discountPercent <= 0 || $discountPercent > 100) {
            throw new RuntimeException('Discount percent must be between 0 and 100.', 422);
        }

        if ($validityDays < 1 || $validityDays > 3650) {
            throw new RuntimeException('Validity must be between 1 and 3650 days.', 422);
        }

        $statement = $this->pdo->prepare(
            'UPDATE welcome_voucher_settings
             SET discount_percent = :discount_percent,
                 validity_days = :validity_days,
                 is_enabled = :is_enabled,
                 updated_at = NOW()
             WHERE id = 1'
        );
        $statement->execute([
            'discount_percent' => round($discountPercent, 2),
            'validity_days' => $validityDays,
            'is_enabled' => $isEnabled ? 1 : 0,
        ]);

        return $this->getSettings();
    }

    public function findByUserId(int $userId): ?array
    {
        $this->ensureSchema();

        $statement = $this->pdo->prepare(
            'SELECT v.id, v.code, v.discount_percent, v.scope_type, v.category_name, v.expires_at, v.is_active,
                    uv.used_at, uv.created_at AS assigned_at, uv.user_id
             FROM user_vouchers uv
             JOIN vouchers v ON v.id = uv.voucher_id
             WHERE uv.user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch();

        return $row ? $this->mapUserVoucher($row) : null;
    }

    /** @return array<int, array> */
    public function allByUserId(int $userId): array
    {
        $found = $this->findByUserId($userId);

        return $found ? [$found] : [];
    }

    public function findOwnerByVoucherId(int $voucherId): ?array
    {
        $this->ensureSchema();

        $statement = $this->pdo->prepare(
            'SELECT user_id, voucher_id, used_at FROM user_vouchers WHERE voucher_id = :voucher_id LIMIT 1'
        );
        $statement->execute(['voucher_id' => $voucherId]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function assignWelcomeVoucher(int $userId, array $voucher): array
    {
        $this->ensureSchema();

        if ($this->findByUserId($userId)) {
            return $this->findByUserId($userId);
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO user_vouchers (user_id, voucher_id, created_at) VALUES (:user_id, :voucher_id, NOW())'
        );
        $statement->execute([
            'user_id' => $userId,
            'voucher_id' => (int) $voucher['id'],
        ]);

        return $this->findByUserId($userId);
    }

    public function markUsed(int $userId, int $voucherId): void
    {
        $this->ensureSchema();

        $statement = $this->pdo->prepare(
            'UPDATE user_vouchers SET used_at = COALESCE(used_at, NOW()) WHERE user_id = :user_id AND voucher_id = :voucher_id'
        );
        $statement->execute([
            'user_id' => $userId,
            'voucher_id' => $voucherId,
        ]);
    }

    public function isUsed(int $voucherId): bool
    {
        $this->ensureSchema();

        $statement = $this->pdo->prepare('SELECT used_at FROM user_vouchers WHERE voucher_id = :voucher_id LIMIT 1');
        $statement->execute(['voucher_id' => $voucherId]);
        $usedAt = $statement->fetchColumn();

        return $usedAt !== false && $usedAt !== null;
    }

    private function mapSettings(array $row): array
    {
        return [
            'discountPercent' => (float) $row['discount_percent'],
            'validityDays' => (int) $row['validity_days'],
            'isEnabled' => (bool) (int) ($row['is_enabled'] ?? 1),
        ];
    }

    private function mapUserVoucher(array $row): array
    {
        $expiresAt = (string) $row['expires_at'];
        $isExpired = $expiresAt !== '' && strtotime($expiresAt) !== false && strtotime($expiresAt) < time();

        return [
            'userId' => (int) $row['user_id'],
            'voucherId' => (int) $row['id'],
            'code' => $row['code'],
            'discountPercent' => (float) $row['discount_percent'],
            'scopeType' => $row['scope_type'],
            'categoryName' => $row['category_name'],
            'expiresAt' => $row['expires_at'],
            'isActive' => (bool) $row['is_active'],
            'usedAt' => $row['used_at'],
            'isUsed' => $row['used_at'] !== null,
            'isExpired' => $isExpired,
            'isValid' => (bool) $row['is_active'] && $row['used_at'] === null && !$isExpired,
            'assignedAt' => $row['assigned_at'],
        ];
    }
}
