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
        private readonly ShippingRepository $shipping,
        private readonly ProductRepository $products,
        private readonly \App\Services\EmailService $email
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
            'status' => $status,
            'paypal_order_id' => $paypalOrderId,
        ];

        return $this->createOrder($customerName, $email, $checkout, null, ['items' => $items]);
    }

    public function findByPayPalOrderId(string $paypalOrderId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE paypal_order_id = :paypal_order_id LIMIT 1');
        $statement->execute(['paypal_order_id' => $paypalOrderId]);
        $order = $statement->fetch();

        return $order ? $this->mapOrder($order) : null;
    }

    public function findByOrderNumber(string $orderNumber): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE order_number = :order_number LIMIT 1');
        $statement->execute(['order_number' => trim($orderNumber)]);
        $order = $statement->fetch();

        return $order ? $this->mapOrder($order) : null;
    }

    public function updatePaypalOrderId(int $orderId, string $paypalOrderId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE orders SET paypal_order_id = :paypal_order_id, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'id' => $orderId,
            'paypal_order_id' => $paypalOrderId,
        ]);
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

            // Reserve stock for the order
            $this->reserveStock($cart['items']);

            if ($userId !== null) {
                // $cart comes from cartWithItems() and carries the carts.id —
                // never pass the user id here or the wrong cart rows are cleared.
                if (isset($cart['id']) && (int) $cart['id'] > 0) {
                    $this->clearCart((int) $cart['id']);
                }
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

    private function reserveStock(array $items): void
    {
        $update = $this->pdo->prepare(
            'UPDATE products SET stock = stock - :quantity, updated_at = NOW() WHERE id = :id'
        );

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];

            // Verify stock is still available
            $check = $this->pdo->prepare('SELECT stock FROM products WHERE id = :id FOR UPDATE');
            $check->execute(['id' => $productId]);
            $product = $check->fetch();

            if (!$product || (int) $product['stock'] < $quantity) {
                throw new RuntimeException('Insufficient stock for product ID ' . $productId, 409);
            }

            $update->execute([
                'id' => $productId,
                'quantity' => $quantity,
            ]);
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

        $cart = null;

        try {
            $cart = $this->cartWithItems($userId);
        } catch (RuntimeException $exception) {
            $cart = null;
        }

        if ($cart === null || $cart['items'] === []) {
            // Fallback: use frontend-supplied items (fixes "Your cart is empty"
            // when the DB cart is out of sync but the UI clearly has items).
            $fallbackItems = $payload['items'] ?? [];

            if ($fallbackItems === []) {
                throw new RuntimeException('Your cart is empty.', 422);
            }

            $guestCheckout = $this->prepareGuestCheckout($payload);

            // Preserve the real DB cart id so post-order cleanup clears the
            // correct cart rows instead of falling back to the user id.
            $dbCartId = null;

            if (is_array($cart) && isset($cart['id'])) {
                $dbCartId = (int) $cart['id'];
            } else {
                try {
                    $existingCart = $this->cartWithItems($userId);
                    $dbCartId = isset($existingCart['id']) ? (int) $existingCart['id'] : null;
                } catch (RuntimeException) {
                    $dbCartId = null;
                }
            }

            // Prefer the voucher discount already stored on the DB cart when
            // the frontend did not send one.
            $dbDiscount = 0.0;

            if (is_array($cart) && isset($cart['discount_amount'])) {
                $dbDiscount = (float) $cart['discount_amount'];
            }

            $payloadDiscount = (float) ($payload['discount'] ?? 0);
            $discount = $payloadDiscount !== 0.0 ? $payloadDiscount : $dbDiscount;
            $subtotal = (float) $guestCheckout['subtotal'];
            $shipping = (float) $guestCheckout['shipping'];
            $total = max($subtotal - $discount, 0) + $shipping;

            return [
                ...$guestCheckout,
                'discount' => $discount,
                'total' => $total,
                'cart' => [
                    'id' => $dbCartId,
                    'items' => $guestCheckout['cart']['items'],
                ],
            ];
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
        // Pure status update — no emails here. Callers (capture / webhook)
        // are responsible for sending exactly one confirmation email, so
        // listing orders or background refreshes never trigger duplicate mail.
        $statement = $this->pdo->prepare(
            'UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'id' => $orderId,
            'status' => $status,
        ]);
    }

    public function findByIdAny(int $orderId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $orderId]);
        $order = $statement->fetch();

        return $order ? $this->mapOrder($order) : null;
    }

    /**
     * Admin fulfillment update: status transition + courier/tracking info.
     * Handles missing fulfillment columns gracefully on older databases.
     */
    public function updateFulfillment(
        int $orderId,
        string $status,
        ?string $courier = null,
        ?string $trackingNumber = null
    ): array {
        $courier = $courier !== null ? trim($courier) : null;
        $trackingNumber = $trackingNumber !== null ? trim($trackingNumber) : null;

        if ($courier === '') {
            $courier = null;
        }

        if ($trackingNumber === '') {
            $trackingNumber = null;
        }

        $hasFulfillmentColumns = $this->hasFulfillmentColumns();

        if ($hasFulfillmentColumns) {
            $statement = $this->pdo->prepare(
                'UPDATE orders
                 SET status = :status,
                     courier = :courier,
                     tracking_number = :tracking_number,
                     shipped_at = CASE WHEN :status_shipped = \'shipped\' THEN COALESCE(shipped_at, NOW()) ELSE shipped_at END,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'id' => $orderId,
                'status' => $status,
                'status_shipped' => $status,
                'courier' => $courier,
                'tracking_number' => $trackingNumber,
            ]);
        } else {
            $this->updateStatus($orderId, $status);
        }

        $order = $this->findByIdAny($orderId);

        if ($order === null) {
            throw new RuntimeException('Order not found.', 404);
        }

        return $order;
    }

    private function hasFulfillmentColumns(): bool
    {
        try {
            $statement = $this->pdo->query('SHOW COLUMNS FROM orders LIKE \'courier\'');
            $row = $statement ? $statement->fetch() : false;

            if (!$row) {
                return false;
            }

            $tracking = $this->pdo->query('SHOW COLUMNS FROM orders LIKE \'tracking_number\'');
            $trackingRow = $tracking ? $tracking->fetch() : false;

            return (bool) $trackingRow;
        } catch (Throwable) {
            return false;
        }
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
            'courier' => $order['courier'] ?? null,
            'trackingNumber' => $order['tracking_number'] ?? null,
            'shippedAt' => $order['shipped_at'] ?? null,
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

    public function restoreStockForOrder(int $orderId): bool
    {
        $order = $this->findById($orderId, null);

        if (!$order) {
            return false;
        }

        $items = $order['items'] ?? [];

        if ($items === []) {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            $update = $this->pdo->prepare(
                'UPDATE products SET stock = stock + :quantity, updated_at = NOW() WHERE id = :id'
            );

            foreach ($items as $item) {
                $productId = (int) $item['productId'];
                $quantity = (int) $item['quantity'];

                $update->execute([
                    'id' => $productId,
                    'quantity' => $quantity,
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function cancelExpiredOrders(int $hours = 1): array
    {
        // NOTE: hours is interpolated (not bound) because MySQL does not
        // allow placeholders inside INTERVAL ... HOUR in prepared statements.
        $hours = max(1, (int) $hours);

        // Find orders with status 'pending' created more than $hours ago
        $statement = $this->pdo->prepare(
            'SELECT * FROM orders WHERE status = :status AND created_at < DATE_SUB(NOW(), INTERVAL ' . $hours . ' HOUR)'
        );
        $statement->execute([
            'status' => 'pending',
        ]);
        $expiredOrders = $statement->fetchAll();

        $cancelled = [];

        foreach ($expiredOrders as $order) {
            $cancelledOrder = $this->cancelSingleExpiredOrder($order);

            if ($cancelledOrder !== null) {
                $cancelled[] = [
                    'id' => $cancelledOrder['id'],
                    'orderNumber' => $cancelledOrder['orderNumber'],
                    'customerEmail' => $cancelledOrder['customerEmail'],
                ];
            }
        }

        return $cancelled;
    }

    /**
     * Cancel one raw DB order row that is already known to be overdue:
     * restores stock, marks cancelled, sends the cancellation email with
     * properly mapped order data. Returns the mapped cancelled order.
     */
    private function cancelSingleExpiredOrder(array $order): ?array
    {
        $orderId = (int) ($order['id'] ?? 0);

        if ($orderId <= 0) {
            return null;
        }

        // Restore stock
        $this->restoreStockForOrder($orderId);

        // Update order status to cancelled
        $update = $this->pdo->prepare('UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id');
        $update->execute([
            'id' => $orderId,
            'status' => 'cancelled',
        ]);

        $mapped = $this->findById($orderId, null);

        if ($mapped === null) {
            return null;
        }

        // Send cancellation email (mapped row carries items + camelCase
        // totals; the raw row does not, which previously produced an
        // empty invoice table in this email).
        if ($this->email) {
            try {
                $this->email->sendOrderCancelledEmail(
                    $mapped['customerEmail'],
                    $mapped['customerName'],
                    array_merge($mapped, ['status' => 'cancelled'])
                );
            } catch (\Throwable) {
                // Log error but don't fail the cancellation
            }
        }

        return $mapped;
    }

    /**
     * If the given mapped order is still pending but older than $hours,
     * cancel it now (stock back, email sent) and return the fresh mapped
     * cancelled order. Returns null when nothing had to be done.
     */
    public function expireOrderIfOverdue(array $order, int $hours = 1): ?array
    {
        if (($order['status'] ?? '') !== 'pending') {
            return null;
        }

        $orderId = (int) ($order['id'] ?? 0);

        if ($orderId <= 0) {
            return null;
        }

        $hours = max(1, (int) $hours);

        $check = $this->pdo->prepare(
            'SELECT * FROM orders WHERE id = :id AND status = :status AND created_at < DATE_SUB(NOW(), INTERVAL ' . $hours . ' HOUR) LIMIT 1'
        );
        $check->execute([
            'id' => $orderId,
            'status' => 'pending',
        ]);
        $overdue = $check->fetch();

        if (!$overdue) {
            return null;
        }

        return $this->cancelSingleExpiredOrder($overdue);
    }
}
