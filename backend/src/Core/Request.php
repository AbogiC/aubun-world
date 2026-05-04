<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $files,
        public readonly array $headers,
        public readonly array $server,
        public array $attributes = []
    ) {
    }

    public static function capture(): self
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
        $rawBody = file_get_contents('php://input') ?: '';
        $decodedJson = json_decode($rawBody !== '' ? $rawBody : '{}', true);
        $body = str_contains($contentType, 'multipart/form-data')
            ? $_POST
            : (is_array($decodedJson) ? $decodedJson : []);

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            $_GET,
            $body,
            $_FILES,
            function_exists('getallheaders') ? getallheaders() : [],
            $_SERVER
        );
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    public function queryParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        foreach ($this->headers as $headerKey => $value) {
            if (strcasecmp($headerKey, $key) === 0) {
                return $value;
            }
        }

        return $default;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
