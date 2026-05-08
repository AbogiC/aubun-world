<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use App\Services\PayPalOrderService;
use RuntimeException;

final class OrderController
{
    private const MANAGER_ROLES = ['manager', 'admin'];

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly CartRepository $carts,
        private readonly PayPalOrderService $paypal
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
        $userId = (int) $request->attribute('user')['id'];
        $order = $this->orders->createFromCart($userId, [
            'first_name' => (string) $request->input('firstName'),
            'last_name' => (string) $request->input('lastName'),
            'email' => (string) $request->input('email'),
            'address' => (string) $request->input('address'),
            'city' => (string) $request->input('city'),
            'country' => (string) $request->input('country'),
            'postal_code' => (string) $request->input('postalCode'),
            'shipping_rate_id' => $request->input('shippingRateId'),
        ]);

        return [
            'message' => 'Order placed successfully.',
            'order' => $order,
            ...$this->carts->cartPayload($userId),
        ];
    }

    public function create(Request $request): array
    {
        $this->ensurePaypalConfigured();

        $userId = (int) $request->attribute('user')['id'];
        $checkout = $this->orders->prepareCheckoutFromCart($userId, $this->checkoutPayload($request));
        $paypalOrder = $this->paypal->createOrder($checkout);

        return [
            ...$paypalOrder,
            'currencyCode' => $this->paypal->currency(),
        ];
    }

    public function capture(Request $request): array
    {
        $this->ensurePaypalConfigured();

        $userId = (int) $request->attribute('user')['id'];
        $paypalOrderId = (string) $request->attribute('orderID');
        $capture = $this->paypal->captureOrder($paypalOrderId);
        $paypalOrder = $this->paypal->getOrder($paypalOrderId);
        $order = $this->orders->createFromCart($userId, [
            ...$this->checkoutPayload($request),
            'status' => $this->resolveOrderStatus($paypalOrder),
            'paypal_order_id' => $paypalOrderId,
        ]);

        return [
            'message' => 'Order placed successfully.',
            'order' => $order,
            'paypalOrder' => $capture,
            'paypalOrderDetails' => $paypalOrder,
            ...$this->carts->cartPayload($userId),
        ];
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
