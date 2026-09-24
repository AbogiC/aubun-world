<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $this->ensureGoogleAuthColumns();
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $this->ensureGoogleAuthColumns();
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByGoogleId(string $googleId): ?array
    {
        $this->ensureGoogleAuthColumns();
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE google_id = :google_id LIMIT 1');
        $statement->execute(['google_id' => $googleId]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    /**
     * Adds google_id / auth_provider / avatar_url columns on existing
     * databases without requiring a manual migration. Safe to call often.
     */
    public function ensureGoogleAuthColumns(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $columns = [];
            $stmt = $this->pdo->query('SHOW COLUMNS FROM users');
            if ($stmt) {
                foreach ($stmt->fetchAll() as $row) {
                    $columns[strtolower((string) ($row['Field'] ?? ''))] = true;
                }
            }

            if (!isset($columns['google_id'])) {
                $this->pdo->exec('ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL DEFAULT NULL UNIQUE');
            }
            if (!isset($columns['auth_provider'])) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN auth_provider VARCHAR(20) NOT NULL DEFAULT 'local'");
            }
            if (!isset($columns['avatar_url'])) {
                $this->pdo->exec('ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL DEFAULT NULL');
            }
            // Google users have no password, so it must be nullable.
            $this->pdo->exec('ALTER TABLE users MODIFY password VARCHAR(255) NULL DEFAULT NULL');
        } catch (\Throwable $exception) {
            error_log('ensureGoogleAuthColumns failed: ' . $exception->getMessage());
        }
    }

    public function all(): array
    {
        $statement = $this->pdo->query(
            'SELECT * FROM users ORDER BY created_at DESC, id DESC'
        );

        $users = $statement ? $statement->fetchAll() : [];

        return array_map(fn (array $user): array => $this->sanitize($user), $users);
    }

    public function create(string $name, string $email, ?string $password, string $role = 'customer'): array
    {
        $this->ensureGoogleAuthColumns();
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, role, password, is_active, created_at, updated_at) VALUES (:name, :email, :role, :password, 1, NOW(), NOW())'
        );
        $statement->execute(compact('name', 'email', 'role', 'password'));

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function createGoogleUser(string $name, string $email, string $googleId, ?string $avatarUrl = null): array
    {
        $this->ensureGoogleAuthColumns();
        $statement = $this->pdo->prepare(
            "INSERT INTO users (name, email, role, password, google_id, auth_provider, avatar_url, is_active, email_verified_at, created_at, updated_at)
             VALUES (:name, :email, 'customer', NULL, :google_id, 'google', :avatar_url, 1, NOW(), NOW(), NOW())"
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'google_id' => $googleId,
            'avatar_url' => $avatarUrl,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function linkGoogleAccount(int $userId, string $googleId, ?string $avatarUrl = null): ?array
    {
        $this->ensureGoogleAuthColumns();
        $user = $this->findById($userId);
        if (!$user) {
            return null;
        }

        $hasPassword = isset($user['password']) && $user['password'] !== null && $user['password'] !== '';
        $provider = $hasPassword ? 'both' : 'google';

        $statement = $this->pdo->prepare(
            "UPDATE users
             SET google_id = :google_id,
                 auth_provider = :provider,
                 avatar_url = COALESCE(:avatar_url, avatar_url),
                 email_verified_at = COALESCE(email_verified_at, NOW()),
                 updated_at = NOW()
             WHERE id = :id"
        );
        $statement->execute([
            'google_id' => $googleId,
            'provider' => $provider,
            'avatar_url' => $avatarUrl,
            'id' => $userId,
        ]);

        return $this->findById($userId);
    }

    public function updateUser(int $userId, ?string $role = null, ?bool $isActive = null): ?array
    {
        $updates = [];
        $params = ['id' => $userId];

        if ($role !== null) {
            $updates[] = 'role = :role';
            $params['role'] = $role;
        }

        if ($isActive !== null) {
            $updates[] = 'is_active = :is_active';
            $params['is_active'] = $isActive ? 1 : 0;
        }

        if ($updates === []) {
            return $this->findById($userId);
        }

        $statement = $this->pdo->prepare(
            'UPDATE users SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = :id'
        );
        $statement->execute($params);

        return $this->findById($userId);
    }

    public function sanitize(array $user): array
    {
        unset($user['password']);
        unset($user['verification_token']);

        $isSubscribed = isset($user['isSubscribed']) && (int) $user['isSubscribed'] === 1;
        $user['isSubscribed'] = $isSubscribed;

        $isActive = isset($user['is_active'])
            ? (bool) (int) $user['is_active']
            : (isset($user['isActive']) ? (bool) $user['isActive'] : true);

        $user['is_active'] = $isActive;
        $user['isActive'] = $isActive;

        // Decode shipping_address JSON if present
        if (isset($user['shipping_address']) && $user['shipping_address']) {
            $user['shipping_address'] = json_decode($user['shipping_address'], true) ?: null;
        } else {
            $user['shipping_address'] = null;
        }

        // Add email_verified boolean (both snake_case and camelCase for frontend compatibility)
        $isVerified = isset($user['email_verified_at']) && $user['email_verified_at'] !== null;
        $user['email_verified'] = $isVerified;
        $user['emailVerified'] = $isVerified;

        // Normalise Google auth fields so frontend/admin can show "login by google".
        $user['auth_provider'] = $user['auth_provider'] ?? 'local';
        $user['authProvider'] = $user['auth_provider'];
        $user['google_id'] = $user['google_id'] ?? null;
        $user['googleId'] = $user['google_id'];
        $user['avatar_url'] = $user['avatar_url'] ?? null;
        $user['avatarUrl'] = $user['avatar_url'];
        $user['login_by_google'] = ($user['auth_provider'] === 'google' || $user['auth_provider'] === 'both');
        $user['loginByGoogle'] = $user['login_by_google'];

        return $user;
    }

    public function markAsSubscribed(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET isSubscribed = 1, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $userId]);

        return $this->findById($userId);
    }

    public function updateProfile(int $userId, string $name, string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET name = :name, email = :email, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['name' => $name, 'email' => $email, 'id' => $userId]);

        return $this->findById($userId);
    }

    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        return $stmt->execute(['password' => $hashedPassword, 'id' => $userId]);
    }

    public function updateShippingAddress(int $userId, string $address, string $city, string $country, string $postalCode): ?array
    {
        $shippingAddress = json_encode([
            'address' => $address,
            'city' => $city,
            'country' => $country,
            'postal_code' => $postalCode,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stmt = $this->pdo->prepare(
            'UPDATE users SET shipping_address = :shipping_address, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['shipping_address' => $shippingAddress, 'id' => $userId]);

        return $this->findById($userId);
    }

    public function setVerificationToken(int $userId, string $token, ?string $expiresAt = null): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET verification_token = :token,
                 verification_token_expires_at = COALESCE(:expires_at, DATE_ADD(NOW(), INTERVAL 24 HOUR))
             WHERE id = :id'
        );
        $stmt->execute([
            'token' => $token,
            'expires_at' => $expiresAt,
            'id' => $userId,
        ]);

        return true;
    }

    public function verifyEmail(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET email_verified_at = NOW(), verification_token = NULL, verification_token_expires_at = NULL WHERE id = :id'
        );
        return $stmt->execute(['id' => $userId]);
    }

    public function findByVerificationToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE verification_token = :token AND verification_token_expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function clearVerificationToken(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET verification_token = NULL, verification_token_expires_at = NULL WHERE id = :id'
        );
        return $stmt->execute(['id' => $userId]);
    }

    public function resetEmailVerification(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET email_verified_at = NULL,
                 verification_token = NULL,
                 verification_token_expires_at = NULL
             WHERE id = :id'
        );
        return $stmt->execute(['id' => $userId]);
    }

    public function hasVerifiedEmail(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT email_verified_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $result = $stmt->fetchColumn();

        return $result !== false && $result !== null;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
