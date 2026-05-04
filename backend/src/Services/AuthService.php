<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class AuthService
{
    public function __construct(private readonly string $secret)
    {
    }

    public function issueToken(int $userId, string $email): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode([
            'sub' => $userId,
            'email' => $email,
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60),
        ]));

        $signature = $this->sign($header . '.' . $payload);

        return $header . '.' . $payload . '.' . $signature;
    }

    public function parseToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            $header = $payload = $signature = '';
        } else {
            [$header, $payload, $signature] = $parts;
        }

        if ($header === '' || $payload === '' || $signature === '') {
            throw new RuntimeException('Malformed authentication token.', 401);
        }

        if (!hash_equals($this->sign($header . '.' . $payload), $signature)) {
            throw new RuntimeException('Invalid authentication token.', 401);
        }

        $decoded = json_decode($this->base64UrlDecode($payload), true);

        if (!is_array($decoded) || ($decoded['exp'] ?? 0) < time()) {
            throw new RuntimeException('Authentication token has expired.', 401);
        }

        return $decoded;
    }

    private function sign(string $data): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $data, $this->secret, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
