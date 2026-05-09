<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;

final class CartRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ProductRepository $products,
        private readonly VoucherRepository $vouchers
    ) {
    }

    public function cartPayload(int $userId): array
    {
        $cart = $this->getOrCreateCart($userId);
        $cart = $this->revalidateDiscount($cart);
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

        if ($normalized === '') {
            throw new RuntimeException('Voucher code is required.', 422);
        }

        $voucher = $this->vouchers->findByCode($normalized);

        if (!$voucher || !$voucher['isActive']) {
            throw new RuntimeException('Invalid promo code.', 422);
        }

        $items = $this->items((int) $cart['id']);
        $expiresAt = strtotime((string) $voucher['expiresAt']);

        if ($expiresAt !== false && $expiresAt < time()) {
            throw new RuntimeException('This voucher has expired.', 422);
        }

        $eligibleSubtotal = $this->eligibleSubtotalForVoucher($items, $voucher);

        if ($eligibleSubtotal <= 0) {
            throw new RuntimeException('This voucher does not apply to any products in your cart.', 422);
        }

        $discount = round($eligibleSubtotal * (((float) $voucher['discountPercent']) / 100), 2);

        $statement = $this->pdo->prepare(
            'UPDATE carts SET discount_code = :discount_code, discount_amount = :discount_amount, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'discount_code' => $voucher['code'],
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

    private function revalidateDiscount(array $cart): array
    {
        $discountCode = trim((string) ($cart['discount_code'] ?? ''));

        if ($discountCode === '') {
            return $cart;
        }

        $voucher = $this->vouchers->findByCode($discountCode);
        $items = $this->items((int) $cart['id']);

        if (!$voucher || !$voucher['isActive']) {
            return $this->resetDiscount($cart);
        }

        $expiresAt = strtotime((string) $voucher['expiresAt']);

        if ($expiresAt !== false && $expiresAt < time()) {
            return $this->resetDiscount($cart);
        }

        $eligibleSubtotal = $this->eligibleSubtotalForVoucher($items, $voucher);

        if ($eligibleSubtotal <= 0) {
            return $this->resetDiscount($cart);
        }

        $discount = round($eligibleSubtotal * (((float) $voucher['discountPercent']) / 100), 2);

        if ((float) $cart['discount_amount'] === $discount) {
            return $cart;
        }

        $statement = $this->pdo->prepare(
            'UPDATE carts
             SET discount_code = :discount_code, discount_amount = :discount_amount, updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'discount_code' => $voucher['code'],
            'discount_amount' => $discount,
            'id' => $cart['id'],
        ]);

        $cart['discount_code'] = $voucher['code'];
        $cart['discount_amount'] = $discount;

        return $cart;
    }

    private function resetDiscount(array $cart): array
    {
        $statement = $this->pdo->prepare(
            'UPDATE carts
             SET discount_code = NULL, discount_amount = 0, updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $cart['id'],
        ]);

        $cart['discount_code'] = null;
        $cart['discount_amount'] = 0;

        return $cart;
    }

    private function items(int $cartId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ci.id, ci.product_id, ci.quantity, ci.size, ci.color, ci.unit_price,
                    p.name, p.image, p.category
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
                'category' => $item['category'],
                'price' => (float) $item['unit_price'],
                'quantity' => (int) $item['quantity'],
                'size' => $item['size'],
                'color' => $item['color'],
            ];
        }, $statement->fetchAll());
    }

    private function eligibleSubtotalForVoucher(array $items, array $voucher): float
    {
        return array_reduce($items, function (float $sum, array $item) use ($voucher): float {
            if (!$this->voucherAppliesToItem($voucher, $item)) {
                return $sum;
            }

            return $sum + ($item['price'] * $item['quantity']);
        }, 0.0);
    }

    private function voucherAppliesToItem(array $voucher, array $item): bool
    {
        return match ($voucher['scopeType']) {
            'category' => strcasecmp((string) $voucher['categoryName'], (string) $item['category']) === 0,
            'products' => in_array((int) $item['productId'], $voucher['productIds'], true),
            default => true,
        };
    }
}
