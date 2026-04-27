<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['category']) && $filters['category'] !== 'All') {
            $conditions[] = 'category = :category';
            $params['category'] = $filters['category'];
        }

        if ($filters['featured'] !== null && $filters['featured'] !== '') {
            $conditions[] = 'featured = :featured';
            $params['featured'] = (int) (bool) $filters['featured'];
        }

        if ($filters['min_price'] !== null && $filters['min_price'] !== '') {
            $conditions[] = 'price >= :min_price';
            $params['min_price'] = (float) $filters['min_price'];
        }

        if ($filters['max_price'] !== null && $filters['max_price'] !== '') {
            $conditions[] = 'price <= :max_price';
            $params['max_price'] = (float) $filters['max_price'];
        }

        $sql = 'SELECT * FROM products';

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ' . $this->sortClause((string) ($filters['sort'] ?? 'featured'));

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return array_map([$this, 'mapProduct'], $statement->fetchAll());
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();

        return $product ? $this->mapProduct($product) : null;
    }

    public function categories(): array
    {
        $statement = $this->pdo->query('SELECT DISTINCT category FROM products ORDER BY category');

        return array_merge(['All'], array_column($statement->fetchAll(), 'category'));
    }

    private function mapProduct(array $product): array
    {
        $product['id'] = (int) $product['id'];
        $product['price'] = (float) $product['price'];
        $product['originalPrice'] = $product['original_price'] !== null ? (float) $product['original_price'] : null;
        $product['rating'] = (float) $product['rating'];
        $product['reviews'] = (int) $product['reviews'];
        $product['featured'] = (bool) $product['featured'];
        $product['sizes'] = json_decode($product['sizes'], true, 512, JSON_THROW_ON_ERROR);
        $product['colors'] = json_decode($product['colors'], true, 512, JSON_THROW_ON_ERROR);

        unset($product['original_price'], $product['created_at'], $product['updated_at']);

        return $product;
    }

    private function sortClause(string $sort): string
    {
        return match ($sort) {
            'price-low' => 'ORDER BY price ASC',
            'price-high' => 'ORDER BY price DESC',
            'rating' => 'ORDER BY rating DESC',
            'newest' => 'ORDER BY created_at DESC, id DESC',
            default => 'ORDER BY featured DESC, id DESC',
        };
    }
}
