<?php

declare(strict_types=1);

namespace App\Config;

final class Config
{
    public static function load(string $envPath): array
    {
        $defaults = [
            'APP_KEY' => 'replace-this-secret-key',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'aubun_world',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
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
            ],
            'db' => [
                'host' => $env['DB_HOST'],
                'port' => (int) $env['DB_PORT'],
                'database' => $env['DB_DATABASE'],
                'username' => $env['DB_USERNAME'],
                'password' => $env['DB_PASSWORD'],
            ],
        ];
    }
}
