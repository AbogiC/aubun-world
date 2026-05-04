<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $body, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($body, JSON_UNESCAPED_SLASHES);
    }

    public static function file(string $path, string $contentType): void
    {
        http_response_code(200);
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
    }
}
