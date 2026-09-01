<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use App\Services\EmailService;
use App\Services\PayPalOrderService;
use RuntimeException;

final class OrderController
{
    private const MANAGER_ROLES = ['manager', 'admin'];

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly CartRepository $carts,
        private readonly PayPalOrderService $paypal,
        private readonly EmailService $email
    ) {
    }

    public function index(Request $request): array
    {
        $user = $request->attribute('user');
        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');
        $orders = in_array($role, self::MANAGER_ROLES, true)
            ? $this->orders->all()
            : $this->orders->allByUser($userId);

        return [
            'orders' => $this->refreshPendingOrders($orders),
        ];
    }

    public function checkout(Request $request): array
    {
        $user = $request->attribute('user');
        $isGuest = !$user || empty($user['id']);
        $userId = $isGuest ? null : (int) $user['id'];

        $payload = [
            'first_name' => (string) $request->input('firstName'),
            'last_name' => (string) $request->input('lastName'),
            'email' => (string) $request->input('email'),
            'address' => (string) $request->input('address'),
            'city' => (string) $request->input('city'),
            'country' => (string) $request->input('country'),
            'postal_code' => (string) $request->input('postalCode'),
            'shipping_rate_id' => $request->input('shippingRateId'),
            'items' => $request->input('items') ?? [],
            'subtotal' => (float) ($request->input('subtotal') ?? 0),
            'discount' => (float) ($request->input('discount') ?? 0),
            'shipping_cost' => (float) ($request->input('shipping_cost') ?? 0),
            'total' => (float) ($request->input('total') ?? 0),
            'shipping_tier_name' => (string) ($request->input('shipping_tier_name') ?? ''),
            'shop_country_name' => (string) ($request->input('shop_country_name') ?? ''),
        ];

        if ($isGuest) {
            $order = $this->orders->createFromGuestCart($payload, $payload['items'] ?? []);
        } else {
            $order = $this->orders->createFromCart($userId, [
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'email' => $payload['email'],
                'address' => $payload['address'],
                'city' => $payload['city'],
                'country' => $payload['country'],
                'postal_code' => $payload['postal_code'],
                'shipping_rate_id' => $payload['shipping_rate_id'],
            ]);
        }

        $this->email->sendOrderConfirmation(
            $order['customerEmail'],
            $order['customerName'],
            $order
        );

        $result = [
            'message' => 'Order placed successfully.',
            'order' => $order,
        ];

        if (!$isGuest) {
            $result['cart'] = $this->carts->cartPayload($userId);
        }

        return $result;
    }

    public function create(Request $request): array
    {
        $this->ensurePaypalConfigured();

        $user = $request->attribute('user');
        $isGuest = !$user || empty($user['id']);
        $userId = $isGuest ? null : (int) $user['id'];

        if ($isGuest) {
            $payload = $this->checkoutPayload($request);
            $checkout = $this->orders->prepareGuestCheckout($payload);
        } else {
            $checkout = $this->orders->prepareCheckoutFromCart($userId, $this->checkoutPayload($request));
        }

        $paypalOrder = $this->paypal->createOrder($checkout);

        return [
            ...$paypalOrder,
            'currencyCode' => $this->paypal->currency(),
        ];
    }

    public function capture(Request $request): array
    {
        $this->ensurePaypalConfigured();

        $user = $request->attribute('user');
        $isGuest = !$user || empty($user['id']);
        $userId = $isGuest ? null : (int) $user['id'];
        $paypalOrderId = (string) $request->attribute('orderID');
        $capture = $this->paypal->captureOrder($paypalOrderId);
        $paypalOrder = $this->paypal->getOrder($paypalOrderId);

        if ($isGuest) {
            $payload = $this->checkoutPayload($request);
            $order = $this->orders->createFromGuestCart([
                ...$payload,
                'status' => $this->resolveOrderStatus($paypalOrder),
                'paypal_order_id' => $paypalOrderId,
            ], $payload['items'] ?? []);
        } else {
            $order = $this->orders->createFromCart($userId, [
                ...$this->checkoutPayload($request),
                'status' => $this->resolveOrderStatus($paypalOrder),
                'paypal_order_id' => $paypalOrderId,
            ]);
        }

        $this->email->sendOrderConfirmation(
            $order['customerEmail'],
            $order['customerName'],
            $order
        );

        $result = [
            'message' => 'Order placed successfully.',
            'order' => $order,
            'paypalOrder' => $capture,
            'paypalOrderDetails' => $paypalOrder,
        ];

        if (!$isGuest) {
            $result['cart'] = $this->carts->cartPayload($userId);
        }

        return $result;
    }

    public function paypalConfig(): array
    {
        return [
            'clientId' => $this->paypal->clientId(),
            'currencyCode' => $this->paypal->currency(),
            'enabled' => $this->paypal->isConfigured(),
        ];
    }

    private function checkoutPayload(Request $request): array
    {
        return [
            'first_name' => (string) $request->input('firstName'),
            'last_name' => (string) $request->input('lastName'),
            'email' => (string) $request->input('email'),
            'address' => (string) $request->input('address'),
            'city' => (string) $request->input('city'),
            'country' => (string) $request->input('country'),
            'postal_code' => (string) $request->input('postalCode'),
            'shipping_rate_id' => $request->input('shippingRateId'),
            'items' => $request->input('items') ?? [],
            'subtotal' => (float) ($request->input('subtotal') ?? 0),
            'discount' => (float) ($request->input('discount') ?? 0),
            'shipping_cost' => (float) ($request->input('shipping_cost') ?? 0),
            'total' => (float) ($request->input('total') ?? 0),
            'shipping_tier_name' => (string) ($request->input('shipping_tier_name') ?? ''),
            'shop_country_name' => (string) ($request->input('shop_country_name') ?? ''),
        ];
    }

    private function ensurePaypalConfigured(): void
    {
        if (!$this->paypal->isConfigured()) {
            throw new RuntimeException('PayPal checkout is not configured yet.', 503);
        }
    }

    private function resolveOrderStatus(array $paypalOrder): string
    {
        return strtoupper((string) ($paypalOrder['status'] ?? '')) === 'COMPLETED'
            ? 'paid'
            : 'pending';
    }

    private function refreshPendingOrders(array $orders): array
    {
        if (!$this->paypal->isConfigured()) {
            return $orders;
        }

        foreach ($orders as &$order) {
            if (($order['status'] ?? '') !== 'pending') {
                continue;
            }

            $paypalOrderId = trim((string) ($order['paypalOrderId'] ?? ''));

            if ($paypalOrderId === '') {
                continue;
            }

            try {
                $paypalOrder = $this->paypal->getOrder($paypalOrderId);
                $resolvedStatus = $this->resolveOrderStatus($paypalOrder);

                if ($resolvedStatus === 'paid') {
                    $this->orders->updateStatus((int) $order['id'], $resolvedStatus);
                    $order['status'] = $resolvedStatus;
                }
            } catch (\Throwable) {
                // Keep the order visible even if PayPal status refresh fails.
            }
        }

        unset($order);

        return $orders;
    }
}