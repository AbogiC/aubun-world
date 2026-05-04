<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;

final class CartRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ProductRepository $products
    ) {
    }

    public function cartPayload(int $userId): array
    {
        $cart = $this->getOrCreateCart($userId);
        $items = $this->items($cart['id']);

        $subtotal = array_reduce($items, static fn (float $sum, array $item): float => $sum + ($item['price'] * $item['quantity']), 0.0);
        $discount = $cart['discount_amount'] !== null ? (float) $cart['discount_amount'] : 0.0;

        return [
            'cart' => [
                'id' => (int) $cart['id'],
                'items' => $items,
                'discountCode' => $cart['discount_code'],
                'discount' => $discount,
                'subtotal' => $subtotal,
                'total' => max($subtotal - $discount, 0),
            ],
        ];
    }

    public function upsertItem(int $userId, array $payload, ?string $customerCountry = null): void
    {
        $product = $this->products->find((int) $payload['product_id'], $customerCountry);

        if (!$product) {
            throw new RuntimeException('Product not found.', 404);
        }

        $cart = $this->getOrCreateCart($userId);
        $statement = $this->pdo->prepare(
            'SELECT id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id AND size = :size AND color = :color LIMIT 1'
        );
        $statement->execute([
            'cart_id' => $cart['id'],
            'product_id' => $payload['product_id'],
            'size' => $payload['size'],
            'color' => $payload['color'],
        ]);
        $existing = $statement->fetch();

        if ($existing) {
            $update = $this->pdo->prepare('UPDATE cart_items SET quantity = quantity + :quantity, updated_at = NOW() WHERE id = :id');
            $update->execute([
                'quantity' => max(1, (int) $payload['quantity']),
                'id' => $existing['id'],
            ]);
        } else {
            $insert = $this->pdo->prepare(
                'INSERT INTO cart_items (cart_id, product_id, quantity, size, color, unit_price, created_at, updated_at)
                 VALUES (:cart_id, :product_id, :quantity, :size, :color, :unit_price, NOW(), NOW())'
            );
            $insert->execute([
                'cart_id' => $cart['id'],
                'product_id' => $payload['product_id'],
                'quantity' => max(1, (int) $payload['quantity']),
                'size' => $payload['size'],
                'color' => $payload['color'],
                'unit_price' => $product['price'],
            ]);
        }
    }

    public function updateItemQuantity(int $userId, int $itemId, int $quantity): void
    {
        $cart = $this->getOrCreateCart($userId);
        $statement = $this->pdo->prepare('UPDATE cart_items SET quantity = :quantity, updated_at = NOW() WHERE id = :id AND cart_id = :cart_id');
        $statement->execute([
            'quantity' => max(1, $quantity),
            'id' => $itemId,
            'cart_id' => $cart['id'],
        ]);
    }

    public function deleteItem(int $userId, int $itemId): void
    {
        $cart = $this->getOrCreateCart($userId);
        $statement = $this->pdo->prepare('DELETE FROM cart_items WHERE id = :id AND cart_id = :cart_id');
        $statement->execute([
            'id' => $itemId,
            'cart_id' => $cart['id'],
        ]);
    }

    public function clear(int $userId): void
    {
        $cart = $this->getOrCreateCart($userId);
        $statement = $this->pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
        $statement->execute(['cart_id' => $cart['id']]);

        $reset = $this->pdo->prepare('UPDATE carts SET discount_code = NULL, discount_amount = 0, updated_at = NOW() WHERE id = :id');
        $reset->execute(['id' => $cart['id']]);
    }

    public function applyDiscount(int $userId, string $code): void
    {
        $normalized = strtoupper(trim($code));
        $cart = $this->getOrCreateCart($userId);

        if ($normalized !== 'LUXURY20') {
            throw new RuntimeException('Invalid promo code.', 422);
        }

        $items = $this->items((int) $cart['id']);
        $subtotal = array_reduce($items, static fn (float $sum, array $item): float => $sum + ($item['price'] * $item['quantity']), 0.0);
        $discount = round($subtotal * 0.2, 2);

        $statement = $this->pdo->prepare(
            'UPDATE carts SET discount_code = :discount_code, discount_amount = :discount_amount, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'discount_code' => $normalized,
            'discount_amount' => $discount,
            'id' => $cart['id'],
        ]);
    }

    private function getOrCreateCart(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM carts WHERE user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $cart = $statement->fetch();

        if ($cart) {
            return $cart;
        }

        $insert = $this->pdo->prepare('INSERT INTO carts (user_id, discount_amount, created_at, updated_at) VALUES (:user_id, 0, NOW(), NOW())');
        $insert->execute(['user_id' => $userId]);

        $statement->execute(['user_id' => $userId]);

        return $statement->fetch();
    }

    private function items(int $cartId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ci.id, ci.product_id, ci.quantity, ci.size, ci.color, ci.unit_price,
                    p.name, p.image
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = :cart_id
             ORDER BY ci.id DESC'
        );
        $statement->execute(['cart_id' => $cartId]);

        return array_map(static function (array $item): array {
            return [
                'id' => (int) $item['id'],
                'productId' => (int) $item['product_id'],
                'name' => $item['name'],
                'image' => $item['image'],
                'price' => (float) $item['unit_price'],
                'quantity' => (int) $item['quantity'],
                'size' => $item['size'],
                'color' => $item['color'],
            ];
        }, $statement->fetchAll());
    }
}
