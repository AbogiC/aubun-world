<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class StockistRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(bool $includeInactive = false): array
    {
        $where = $includeInactive ? '' : 'WHERE is_active = 1';

        $statement = $this->pdo->query(
            "SELECT id, name, region, type, icon, address, city, url, sort_order, is_active, created_at, updated_at
             FROM stockists
             {$where}
             ORDER BY sort_order ASC, name ASC, id ASC"
        );

        return array_map(fn (array $row): array => $this->mapStockist($row), $statement->fetchAll());
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, region, type, icon, address, city, url, sort_order, is_active, created_at, updated_at
             FROM stockists
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->mapStockist($row) : null;
    }

    public function create(array $payload): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO stockists (name, region, type, icon, address, city, url, sort_order, is_active, created_at, updated_at)
             VALUES (:name, :region, :type, :icon, :address, :city, :url, :sort_order, :is_active, NOW(), NOW())'
        );
        $statement->execute($this->persistedStockist($payload));

        $id = (int) $this->pdo->lastInsertId();

        return $this->find($id);
    }

    public function update(int $id, array $payload): ?array
    {
        $statement = $this->pdo->prepare(
            'UPDATE stockists SET
                name = :name,
                region = :region,
                type = :type,
                icon = :icon,
                address = :address,
                city = :city,
                url = :url,
                sort_order = :sort_order,
                is_active = :is_active,
                updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            ...$this->persistedStockist($payload),
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM stockists WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function persistedStockist(array $payload): array
    {
        return [
            'name' => $payload['name'],
            'region' => $payload['region'],
            'type' => $payload['type'],
            'icon' => $payload['icon'],
            'address' => $payload['address'],
            'city' => $payload['city'],
            'url' => $payload['url'] !== '' ? $payload['url'] : null,
            'sort_order' => $payload['sortOrder'],
            'is_active' => $payload['isActive'] ? 1 : 0,
        ];
    }

    private function mapStockist(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'region' => $row['region'],
            'type' => $row['type'],
            'icon' => $row['icon'],
            'address' => $row['address'],
            'city' => $row['city'],
            'url' => $row['url'],
            'sortOrder' => (int) $row['sort_order'],
            'isActive' => (bool) $row['is_active'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }
}
