<?php

declare(strict_types=1);

namespace App\Config;

final class Config
{
    public static function load(string $envPath): array
    {
        $defaults = [
            'APP_KEY' => 'replace-this-secret-key',
            'BASE_URL' => 'http://localhost:5173',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'aubun_world',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'PAYPAL_CLIENT_ID' => '',
            'PAYPAL_CLIENT_SECRET' => '',
            'PAYPAL_BASE_URL' => 'https://api-m.sandbox.paypal.com',
            'PAYPAL_CURRENCY' => 'USD',
            'SMTP_HOST' => 'smtpout.secureserver.net',
            'SMTP_PORT' => '587',
            'SMTP_USERNAME' => 'no-reply@aubunworld.com',
            'SMTP_PASSWORD' => '',
            'SMTP_ENCRYPTION' => 'tls',
            'SMTP_FROM_EMAIL' => 'no-reply@aubunworld.com',
            'SMTP_FROM_NAME' => 'AUBUN WORLD',
        ];

        $env = $defaults;

        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
        }

        return [
            'app' => [
                'key' => $env['APP_KEY'],
                'base_url' => $env['BASE_URL'] ?? 'http://localhost:5173',
                'api_base_url' => $env['API_BASE_URL'] ?? ($env['BASE_URL'] ?? 'http://localhost:5173'),
            ],
            'db' => [
                'host' => $env['DB_HOST'],
                'port' => (int) $env['DB_PORT'],
                'database' => $env['DB_DATABASE'],
                'username' => $env['DB_USERNAME'],
                'password' => $env['DB_PASSWORD'],
            ],
            'paypal' => [
                'client_id' => $env['PAYPAL_CLIENT_ID'] ?? '',
                'client_secret' => $env['PAYPAL_CLIENT_SECRET'] ?? '',
                'base_url' => $env['PAYPAL_BASE_URL'] ?? 'https://api-m.sandbox.paypal.com',
                'currency' => strtoupper($env['PAYPAL_CURRENCY'] ?? 'USD'),
            ],
            'smtp' => [
                'host' => $env['SMTP_HOST'] ?? 'smtpout.secureserver.net',
                'port' => (int) ($env['SMTP_PORT'] ?? 587),
                'username' => $env['SMTP_USERNAME'] ?? 'noreply@aubunworld.com',
                'password' => $env['SMTP_PASSWORD'] ?? '',
                'encryption' => $env['SMTP_ENCRYPTION'] ?? 'tls',
                'from_email' => $env['SMTP_FROM_EMAIL'] ?? 'noreply@aubunworld.com',
                'from_name' => $env['SMTP_FROM_NAME'] ?? 'AUBUN WORLD',
            ],
        ];
    }
}
