<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;
use Throwable;

final class OrderRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ShippingRepository $shipping
    )
    {
    }

    public function createFromCart(int $userId, array $payload): array
    {
        $checkout = $this->prepareCheckoutFromCart($userId, $payload);

        return $this->createOrder($checkout['customer_name'], $checkout['email'], $checkout, $userId, $checkout['cart']);
    }

    public function createFromGuestCart(array $payload, array $items): array
    {
        $customerName = trim(sprintf(
            '%s %s',
            (string) ($payload['first_name'] ?? ''),
            (string) ($payload['last_name'] ?? '')
        ));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $address = trim((string) ($payload['address'] ?? ''));
        $city = trim((string) ($payload['city'] ?? ''));
        $country = trim((string) ($payload['country'] ?? ''));
        $postalCode = trim((string) ($payload['postal_code'] ?? ''));
        $shippingRateId = (int) ($payload['shipping_rate_id'] ?? 0);
        $discount = (float) ($payload['discount'] ?? 0);
        $subtotal = (float) ($payload['subtotal'] ?? 0);
        $shippingCost = (float) ($payload['shipping_cost'] ?? 0);
        $total = (float) ($payload['total'] ?? 0);
        $shippingTierName = (string) ($payload['shipping_tier_name'] ?? '');
        $shopCountryName = (string) ($payload['shop_country_name'] ?? '');
        $status = trim((string) ($payload['status'] ?? 'pending')) ?: 'pending';
        $paypalOrderId = trim((string) ($payload['paypal_order_id'] ?? '')) ?: null;

        if ($customerName === '' || $email === '' || $address === '' || $city === '' || $country === '' || $postalCode === '') {
            throw new RuntimeException('Checkout data is incomplete.', 422);
        }

        if ($items === []) {
            throw new RuntimeException('Your cart is empty.', 422);
        }

        $availableRates = $this->shipping->shippingOptionsForCountry($country);

        if ($availableRates === null || $availableRates['shippingRates'] === []) {
            throw new RuntimeException('Shipping is not available for the selected country yet.', 422);
        }

        if (count($availableRates['shippingRates']) === 1 && $shippingRateId <= 0) {
            $selectedRate = $availableRates['shippingRates'][0];
        } else {
            if ($shippingRateId <= 0) {
                throw new RuntimeException('Please choose a shipping option.', 422);
            }

            $selected = $this->shipping->shippingRateForCountry($country, $shippingRateId);

            if ($selected === null) {
                throw new RuntimeException('Selected shipping option is not valid for this country.', 422);
            }

            $selectedRate = $selected['shippingRate'];
        }

        $shipping = (float) $selectedRate['shippingCost'];

        $checkout = [
            'customer_name' => $customerName,
            'email' => $email,
            'address' => $address,
            'city' => $city,
            'country' => $country,
            'postal_code' => $postalCode,
            'cart' => ['items' => $items],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'available_rates' => $availableRates,
            'selected_rate' => $selectedRate,
            'shipping' => $shipping,
            'total' => $total,
        ];

        return $this->createOrder($customerName, $email, $checkout, null, ['items' => $items]);
    }

    private function createOrder(string $customerName, string $email, array $checkout, ?int $userId, array $cart): array
    {
        $status = trim((string) ($checkout['status'] ?? 'pending')) ?: 'pending';
        $paypalOrderId = trim((string) ($checkout['paypal_order_id'] ?? '')) ?: null;
        $customerName = $checkout['customer_name'];
        $address = $checkout['address'];
        $city = $checkout['city'];
        $country = $checkout['country'];
        $postalCode = $checkout['postal_code'];
        $subtotal = $checkout['subtotal'];
        $discount = $checkout['discount'];
        $selectedRate = $checkout['selected_rate'];
        $shipping = $checkout['shipping'];
        $total = $checkout['total'];
        $availableRates = $checkout['available_rates'];

        try {
            $this->pdo->beginTransaction();

            $orderNumber = $this->generateOrderNumber();
            $insertOrder = $this->pdo->prepare(
                'INSERT INTO orders (
                    user_id, order_number, status, paypal_order_id, customer_name, customer_email,
                    shipping_address, shipping_city, shipping_country, shipping_postal_code,
                    shipping_shop_country, shipping_tier_name,
                    subtotal_amount, discount_amount, shipping_amount, total_amount, created_at, updated_at
                 ) VALUES (
                    :user_id, :order_number, :status, :paypal_order_id, :customer_name, :customer_email,
                    :shipping_address, :shipping_city, :shipping_country, :shipping_postal_code,
                    :shipping_shop_country, :shipping_tier_name,
                    :subtotal_amount, :discount_amount, :shipping_amount, :total_amount, NOW(), NOW()
                 )'
            );
            $insertOrder->execute([
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'status' => $status,
                'paypal_order_id' => $paypalOrderId,
                'customer_name' => $customerName,
                'customer_email' => $email,
                'shipping_address' => $address,
                'shipping_city' => $city,
                'shipping_country' => $country,
                'shipping_postal_code' => $postalCode,
                'shipping_shop_country' => $availableRates['shopCountryName'],
                'shipping_tier_name' => $selectedRate['tierName'],
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'shipping_amount' => $shipping,
                'total_amount' => $total,
            ]);

            $orderId = (int) $this->pdo->lastInsertId();
            $insertItem = $this->pdo->prepare(
                'INSERT INTO order_items (
                    order_id, product_id, product_name, product_image, quantity, size, color, unit_price, line_total, created_at, updated_at
                 ) VALUES (
                    :order_id, :product_id, :product_name, :product_image, :quantity, :size, :color, :unit_price, :line_total, NOW(), NOW()
                 )'
            );

            foreach ($cart['items'] as $item) {
                $insertItem->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'product_image' => $item['image'],
                    'quantity' => $item['quantity'],
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);
            }

            if ($userId !== null) {
                $this->clearCart($userId);
            }

            $this->pdo->commit();

            $order = $this->findById($orderId, $userId);

            return $order ?? throw new RuntimeException('Unable to load the created order.', 500);
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function prepareCheckoutFromCart(int $userId, array $payload): array
    {
        $customerName = trim(sprintf(
            '%s %s',
            (string) ($payload['first_name'] ?? ''),
            (string) ($payload['last_name'] ?? '')
        ));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $address = trim((string) ($payload['address'] ?? ''));
        $city = trim((string) ($payload['city'] ?? ''));
        $country = trim((string) ($payload['country'] ?? ''));
        $postalCode = trim((string) ($payload['postal_code'] ?? ''));
        $shippingRateId = (int) ($payload['shipping_rate_id'] ?? 0);

        if ($customerName === '' || $email === '' || $address === '' || $city === '' || $country === '' || $postalCode === '') {
            throw new RuntimeException('Checkout data is incomplete.', 422);
        }

        $cart = $this->cartWithItems($userId);

        if ($cart['items'] === []) {
            throw new RuntimeException('Your cart is empty.', 422);
        }

        $subtotal = array_reduce(
            $cart['items'],
            static fn (float $sum, array $item): float => $sum + $item['line_total'],
            0.0
        );
        $discount = (float) $cart['discount_amount'];
        $availableRates = $this->shipping->shippingOptionsForCountry($country);

        if ($availableRates === null || $availableRates['shippingRates'] === []) {
            throw new RuntimeException('Shipping is not available for the selected country yet.', 422);
        }

        if (count($availableRates['shippingRates']) === 1 && $shippingRateId <= 0) {
            $selectedRate = $availableRates['shippingRates'][0];
        } else {
            if ($shippingRateId <= 0) {
                throw new RuntimeException('Please choose a shipping option.', 422);
            }

            $selected = $this->shipping->shippingRateForCountry($country, $shippingRateId);

            if ($selected === null) {
                throw new RuntimeException('Selected shipping option is not valid for this country.', 422);
            }

            $selectedRate = $selected['shippingRate'];
        }

        $shipping = (float) $selectedRate['shippingCost'];
        $total = max($subtotal - $discount, 0) + $shipping;

        return [
            'customer_name' => $customerName,
            'email' => $email,
            'address' => $address,
            'city' => $city,
            'country' => $country,
            'postal_code' => $postalCode,
            'cart' => $cart,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'available_rates' => $availableRates,
            'selected_rate' => $selectedRate,
            'shipping' => $shipping,
            'total' => $total,
        ];
    }

    public function prepareGuestCheckout(array $payload): array
    {
        $customerName = trim(sprintf(
            '%s %s',
            (string) ($payload['first_name'] ?? ''),
            (string) ($payload['last_name'] ?? '')
        ));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $address = trim((string) ($payload['address'] ?? ''));
        $city = trim((string) ($payload['city'] ?? ''));
        $country = trim((string) ($payload['country'] ?? ''));
        $postalCode = trim((string) ($payload['postal_code'] ?? ''));
        $shippingRateId = (int) ($payload['shipping_rate_id'] ?? 0);

        if ($customerName === '' || $email === '' || $address === '' || $city === '' || $country === '' || $postalCode === '') {
            throw new RuntimeException('Checkout data is incomplete.', 422);
        }

        $items = $payload['items'] ?? [];

        if ($items === []) {
            throw new RuntimeException('Your cart is empty.', 422);
        }

        $subtotal = (float) ($payload['subtotal'] ?? array_reduce(
            $items,
            static fn (float $sum, array $item): float => $sum + ($item['unit_price'] * $item['quantity']),
            0.0
        ));
        $discount = (float) ($payload['discount'] ?? 0);
        $availableRates = $this->shipping->shippingOptionsForCountry($country);

        if ($availableRates === null || $availableRates['shippingRates'] === []) {
            throw new RuntimeException('Shipping is not available for the selected country yet.', 422);
        }

        if (count($availableRates['shippingRates']) === 1 && $shippingRateId <= 0) {
            $selectedRate = $availableRates['shippingRates'][0];
        } else {
            if ($shippingRateId <= 0) {
                throw new RuntimeException('Please choose a shipping option.', 422);
            }

            $selected = $this->shipping->shippingRateForCountry($country, $shippingRateId);

            if ($selected === null) {
                throw new RuntimeException('Selected shipping option is not valid for this country.', 422);
            }

            $selectedRate = $selected['shippingRate'];
        }

        $shipping = (float) $selectedRate['shippingCost'];
        $total = max($subtotal - $discount, 0) + $shipping;

        return [
            'customer_name' => $customerName,
            'email' => $email,
            'address' => $address,
            'city' => $city,
            'country' => $country,
            'postal_code' => $postalCode,
            'cart' => ['items' => $items],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'available_rates' => $availableRates,
            'selected_rate' => $selectedRate,
            'shipping' => $shipping,
            'total' => $total,
        ];
    }

    public function allByUser(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY id DESC');
        $statement->execute(['user_id' => $userId]);
        $orders = $statement->fetchAll();

        return array_map(fn (array $order): array => $this->mapOrder($order), $orders);
    }

    public function all(): array
    {
        $statement = $this->pdo->query('SELECT * FROM orders ORDER BY id DESC');
        $orders = $statement->fetchAll();

        return array_map(fn (array $order): array => $this->mapOrder($order), $orders);
    }

    public function updateStatus(int $orderId, string $status): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'id' => $orderId,
            'status' => $status,
        ]);
    }

    private function findById(int $orderId, ?int $userId): ?array
    {
        if ($userId !== null) {
            $statement = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1');
            $statement->execute([
                'id' => $orderId,
                'user_id' => $userId,
            ]);
        } else {
            $statement = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $orderId]);
        }
        $order = $statement->fetch();

        return $order ? $this->mapOrder($order) : null;
    }

    private function mapOrder(array $order): array
    {
        return [
            'id' => (int) $order['id'],
            'userId' => $order['user_id'] !== null ? (int) $order['user_id'] : null,
            'orderNumber' => $order['order_number'],
            'status' => $order['status'],
            'paypalOrderId' => $order['paypal_order_id'],
            'customerName' => $order['customer_name'],
            'customerEmail' => $order['customer_email'],
            'shippingAddress' => $order['shipping_address'],
            'shippingCity' => $order['shipping_city'],
            'shippingCountry' => $order['shipping_country'],
            'shippingPostalCode' => $order['shipping_postal_code'],
            'shippingShopCountry' => $order['shipping_shop_country'],
            'shippingTierName' => $order['shipping_tier_name'],
            'subtotal' => (float) $order['subtotal_amount'],
            'discount' => (float) $order['discount_amount'],
            'shipping' => (float) $order['shipping_amount'],
            'total' => (float) $order['total_amount'],
            'createdAt' => $order['created_at'],
            'items' => $this->items((int) $order['id']),
        ];
    }

    private function items(int $orderId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
        $statement->execute(['order_id' => $orderId]);

        return array_map(static function (array $item): array {
            return [
                'id' => (int) $item['id'],
                'productId' => (int) $item['product_id'],
                'name' => $item['product_name'],
                'image' => $item['product_image'],
                'quantity' => (int) $item['quantity'],
                'size' => $item['size'],
                'color' => $item['color'],
                'price' => (float) $item['unit_price'],
                'lineTotal' => (float) $item['line_total'],
            ];
        }, $statement->fetchAll());
    }

    private function cartWithItems(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM carts WHERE user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $cart = $statement->fetch();

        if (!$cart) {
            throw new RuntimeException('Cart not found.', 404);
        }

        $itemsStatement = $this->pdo->prepare(
            'SELECT ci.id, ci.product_id, ci.quantity, ci.size, ci.color, ci.unit_price, p.name, p.image
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = :cart_id
             ORDER BY ci.id ASC'
        );
        $itemsStatement->execute(['cart_id' => $cart['id']]);
        $items = array_map(static function (array $item): array {
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            return [
                'id' => (int) $item['id'],
                'product_id' => (int) $item['product_id'],
                'quantity' => $quantity,
                'size' => $item['size'],
                'color' => $item['color'],
                'unit_price' => $unitPrice,
                'line_total' => $quantity * $unitPrice,
                'name' => $item['name'],
                'image' => $item['image'],
            ];
        }, $itemsStatement->fetchAll());

        $cart['items'] = $items;
        $cart['discount_amount'] = (float) $cart['discount_amount'];

        return $cart;
    }

    private function clearCart(int $cartId): void
    {
        $deleteItems = $this->pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
        $deleteItems->execute(['cart_id' => $cartId]);

        $resetCart = $this->pdo->prepare(
            'UPDATE carts SET discount_code = NULL, discount_amount = 0, updated_at = NOW() WHERE id = :id'
        );
        $resetCart->execute(['id' => $cartId]);
    }

    private function generateOrderNumber(): string
    {
        return 'AUB-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
