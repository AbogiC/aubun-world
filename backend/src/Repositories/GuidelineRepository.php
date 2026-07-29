<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class GuidelineRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, type, title, content, sort_order, is_active
             FROM guidelines
             ORDER BY type ASC, sort_order ASC, id ASC'
        );

        return array_map(fn (array $row): array => $this->mapGuideline($row), $statement->fetchAll());
    }

    public function findByType(string $type): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, type, title, content, sort_order, is_active
             FROM guidelines
             WHERE type = :type
             ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute(['type' => $type]);

        return array_map(fn (array $row): array => $this->mapGuideline($row), $statement->fetchAll());
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, type, title, content, sort_order, is_active
             FROM guidelines
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->mapGuideline($row) : null;
    }

    public function create(array $payload): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO guidelines (type, title, content, sort_order, is_active, created_at, updated_at)
             VALUES (:type, :title, :content, :sort_order, :is_active, NOW(), NOW())'
        );
        $statement->execute($this->persistedGuideline($payload));

        $id = (int) $this->pdo->lastInsertId();

        return $this->find($id);
    }

    public function update(int $id, array $payload): ?array
    {
        $statement = $this->pdo->prepare(
            'UPDATE guidelines SET
                type = :type,
                title = :title,
                content = :content,
                sort_order = :sort_order,
                is_active = :is_active,
                updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            ...$this->persistedGuideline($payload),
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM guidelines WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function persistedGuideline(array $payload): array
    {
        return [
            'type' => $payload['type'],
            'title' => $payload['title'],
            'content' => json_encode($payload['content'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'sort_order' => $payload['sortOrder'],
            'is_active' => $payload['isActive'] ? 1 : 0,
        ];
    }

    private function mapGuideline(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'type' => $row['type'],
            'title' => $row['title'],
            'content' => json_decode($row['content'], true) ?? [],
            'sortOrder' => (int) $row['sort_order'],
            'isActive' => (bool) $row['is_active'],
        ];
    }
}
