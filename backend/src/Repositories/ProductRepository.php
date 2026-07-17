<?php

declare(strict_types=1);

namespace App\Repositories;

use JsonException;
use PDO;
use RuntimeException;
use Throwable;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(array $filters = [], ?string $customerCountry = null): array
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

        return $this->mapProducts($statement->fetchAll(), $customerCountry);
    }

    public function find(int $id, ?string $customerCountry = null): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();

        if (!$product) {
            return null;
        }

        $countryPrices = $this->countryPricesByProductIds([(int) $product['id']]);

        return $this->mapProduct(
            $product,
            $countryPrices[(int) $product['id']] ?? [],
            $customerCountry
        );
    }

    public function categories(): array
    {
        $statement = $this->pdo->query('SELECT DISTINCT category FROM products ORDER BY category');

        return array_merge(['All'], array_column($statement->fetchAll(), 'category'));
    }

    public function create(array $payload): array
    {
        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare(
                'INSERT INTO products (
                    name, category, price, original_price, image, description, rating, reviews, sizes, colors, featured, is_showed, created_at, updated_at
                ) VALUES (
                    :name, :category, :price, :original_price, :image, :description, :rating, :reviews, :sizes, :colors, :featured, :is_showed, NOW(), NOW()
                )'
            );
            $statement->execute($this->persistedProduct($payload));

            $productId = (int) $this->pdo->lastInsertId();
            $this->replaceCountryPrices($productId, $payload['countryPrices'] ?? []);
            $this->pdo->commit();

            return $this->find($productId);
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function update(int $id, array $payload): ?array
    {
        try {
            $this->pdo->beginTransaction();

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
                    is_showed = :is_showed,
                    updated_at = NOW()
                WHERE id = :id'
            );

            $statement->execute([
                'id' => $id,
                ...$this->persistedProduct($payload),
            ]);

            $this->replaceCountryPrices($id, $payload['countryPrices'] ?? []);
            $this->pdo->commit();

            return $this->find($id);
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function mapProducts(array $products, ?string $customerCountry = null): array
    {
        $productIds = array_map(
            static fn (array $product): int => (int) $product['id'],
            $products
        );
        $countryPrices = $this->countryPricesByProductIds($productIds);

        return array_map(
            fn (array $product): array => $this->mapProduct(
                $product,
                $countryPrices[(int) $product['id']] ?? [],
                $customerCountry
            ),
            $products
        );
    }

    private function mapProduct(array $product, array $countryPrices = [], ?string $customerCountry = null): array
    {
        $product['id'] = (int) $product['id'];
        $product['basePrice'] = (float) $product['price'];
        $product['countryPrices'] = $countryPrices;
        $product['price'] = $this->effectivePrice($product['basePrice'], $countryPrices, $customerCountry);
        $product['originalPrice'] = $product['original_price'] !== null ? (float) $product['original_price'] : null;
        $product['rating'] = (float) $product['rating'];
        $product['reviews'] = (int) $product['reviews'];
        $product['featured'] = (bool) $product['featured'];
        $product['isShowed'] = (bool) $product['is_showed'];
        $product['sizes'] = json_decode($product['sizes'], true, 512, JSON_THROW_ON_ERROR);
        $product['colors'] = json_decode($product['colors'], true, 512, JSON_THROW_ON_ERROR);

        unset($product['original_price'], $product['is_showed'], $product['created_at'], $product['updated_at']);

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
                'is_showed' => $payload['isShowed'] ? 1 : 0,
            ];
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid product sizes or colors payload.', 422, $exception);
        }
    }

    private function replaceCountryPrices(int $productId, array $countryPrices): void
    {
        $delete = $this->pdo->prepare('DELETE FROM price_country WHERE product_id = :product_id');
        $delete->execute(['product_id' => $productId]);

        if ($countryPrices === []) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO price_country (product_id, country_name, price, created_at, updated_at)
             VALUES (:product_id, :country_name, :price, NOW(), NOW())'
        );

        foreach ($countryPrices as $entry) {
            $insert->execute([
                'product_id' => $productId,
                'country_name' => $entry['countryName'],
                'price' => $entry['price'],
            ]);
        }
    }

    private function countryPricesByProductIds(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
        $statement = $this->pdo->prepare(
            "SELECT product_id, country_name, price
             FROM price_country
             WHERE product_id IN ($placeholders)
             ORDER BY country_name ASC"
        );
        $statement->execute($productIds);

        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $productId = (int) $row['product_id'];
            $grouped[$productId] ??= [];
            $grouped[$productId][] = [
                'countryName' => $row['country_name'],
                'price' => (float) $row['price'],
            ];
        }

        return $grouped;
    }

    private function effectivePrice(float $basePrice, array $countryPrices, ?string $customerCountry): float
    {
        if ($customerCountry === null) {
            return $basePrice;
        }

        foreach ($countryPrices as $entry) {
            if (strcasecmp((string) $entry['countryName'], $customerCountry) === 0) {
                return (float) $entry['price'];
            }
        }

        return $basePrice;
    }
}
