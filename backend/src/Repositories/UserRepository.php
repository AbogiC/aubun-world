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
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function create(string $name, string $email, string $password, string $role = 'customer'): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, role, password, created_at, updated_at) VALUES (:name, :email, :role, :password, NOW(), NOW())'
        );
        $statement->execute(compact('name', 'email', 'role', 'password'));

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function sanitize(array $user): array
    {
        unset($user['password']);

        // Decode shipping_address JSON if present
        if (isset($user['shipping_address']) && $user['shipping_address']) {
            $user['shipping_address'] = json_decode($user['shipping_address'], true) ?: null;
        } else {
            $user['shipping_address'] = null;
        }

        // Add email_verified boolean
        $user['email_verified'] = isset($user['email_verified_at']) && $user['email_verified_at'] !== null;

        return $user;
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
            'UPDATE users SET verification_token = :token, verification_token_expires_at = :expires_at WHERE id = :id'
        );
        return $stmt->execute([
            'token' => $token,
            'expires_at' => $expiresAt,
            'id' => $userId,
        ]);
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
