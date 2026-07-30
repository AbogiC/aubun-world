<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class NewsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(?string $region = null): array
    {
        if ($region !== null && $region !== '') {
            $statement = $this->pdo->prepare(
                'SELECT id, category, title, excerpt, content, author, read_time, gradient, icon, region, is_published, published_at, created_at, updated_at
                 FROM news
                 WHERE region IS NULL OR region = :region
                 ORDER BY COALESCE(published_at, created_at) DESC, id DESC'
            );
            $statement->execute(['region' => $region]);
        } else {
            $statement = $this->pdo->query(
                'SELECT id, category, title, excerpt, content, author, read_time, gradient, icon, region, is_published, published_at, created_at, updated_at
                 FROM news
                 WHERE region IS NULL
                 ORDER BY COALESCE(published_at, created_at) DESC, id DESC'
            );
        }

        return array_map(fn (array $row): array => $this->mapNews($row), $statement->fetchAll());
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, category, title, excerpt, content, author, read_time, gradient, icon, region, is_published, published_at, created_at, updated_at
             FROM news
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->mapNews($row) : null;
    }

    public function create(array $payload): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO news (category, title, excerpt, content, author, read_time, gradient, icon, region, is_published, published_at, created_at, updated_at)
             VALUES (:category, :title, :excerpt, :content, :author, :read_time, :gradient, :icon, :region, :is_published, :published_at, NOW(), NOW())'
        );
        $statement->execute($this->persistedNews($payload));

        $id = (int) $this->pdo->lastInsertId();

        return $this->find($id);
    }

    public function update(int $id, array $payload): ?array
    {
        $statement = $this->pdo->prepare(
            'UPDATE news SET
                category = :category,
                title = :title,
                excerpt = :excerpt,
                content = :content,
                author = :author,
                read_time = :read_time,
                gradient = :gradient,
                icon = :icon,
                region = :region,
                is_published = :is_published,
                published_at = :published_at,
                updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            ...$this->persistedNews($payload),
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM news WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function persistedNews(array $payload): array
    {
        return [
            'category' => $payload['category'],
            'title' => $payload['title'],
            'excerpt' => $payload['excerpt'],
            'content' => $payload['content'] ?? null,
            'author' => $payload['author'],
            'read_time' => $payload['readTime'],
            'gradient' => $payload['gradient'],
            'icon' => $payload['icon'],
            'region' => $payload['region'] !== '' ? $payload['region'] : null,
            'is_published' => $payload['isPublished'] ? 1 : 0,
            'published_at' => $payload['isPublished'] ? (date('Y-m-d H:i:s')) : null,
        ];
    }

    private function mapNews(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'category' => $row['category'],
            'title' => $row['title'],
            'excerpt' => $row['excerpt'],
            'content' => $row['content'],
            'author' => $row['author'],
            'readTime' => $row['read_time'],
            'gradient' => $row['gradient'],
            'icon' => $row['icon'],
            'region' => $row['region'],
            'isPublished' => (bool) $row['is_published'],
            'publishedAt' => $row['published_at'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }
}
