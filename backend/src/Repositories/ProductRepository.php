<?php

declare(strict_types=1);

namespace App\Repositories;

use JsonException;
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

    public function create(array $payload): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO products (
                name, category, price, original_price, image, description, rating, reviews, sizes, colors, featured, created_at, updated_at
            ) VALUES (
                :name, :category, :price, :original_price, :image, :description, :rating, :reviews, :sizes, :colors, :featured, NOW(), NOW()
            )'
        );
        $statement->execute($this->persistedProduct($payload));

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $payload): ?array
    {
        $statement = $this->pdo->prepare(
            'UPDATE products SET
                name = :name,
                category = :category,
                price = :price,
                original_price = :original_price,
                image = :image,
                description = :description,
                rating = :rating,
                reviews = :reviews,
                sizes = :sizes,
                colors = :colors,
                featured = :featured,
                updated_at = NOW()
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            ...$this->persistedProduct($payload),
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
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

    private function persistedProduct(array $payload): array
    {
        try {
            return [
                'name' => $payload['name'],
                'category' => $payload['category'],
                'price' => $payload['price'],
                'original_price' => $payload['originalPrice'],
                'image' => $payload['image'],
                'description' => $payload['description'],
                'rating' => $payload['rating'],
                'reviews' => $payload['reviews'],
                'sizes' => json_encode($payload['sizes'], JSON_THROW_ON_ERROR),
                'colors' => json_encode($payload['colors'], JSON_THROW_ON_ERROR),
                'featured' => $payload['featured'] ? 1 : 0,
            ];
        } catch (JsonException $exception) {
            throw new \RuntimeException('Invalid product sizes or colors payload.', 422, $exception);
        }
    }
}
