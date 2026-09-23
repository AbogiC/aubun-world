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
            'payment_method' => (string) ($request->input('paymentMethod') ?? $request->input('payment_method') ?? 'paypal'),
            'payment_method_label' => (string) ($request->input('paymentMethodLabel') ?? $request->input('payment_method_label') ?? 'PayPal'),
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

        // Send "awaiting payment" email for pending orders.
        // Do not fail checkout if SMTP is unavailable — order is already created.
        if (($order['status'] ?? 'pending') === 'pending') {
            $order['paymentMethod'] = $payload['payment_method'] ?: 'paypal';
            $order['paymentMethodLabel'] = $payload['payment_method_label'] ?: 'PayPal';
            try {
                $this->email->sendPaymentPendingEmail(
                    $order['customerEmail'],
                    $order['customerName'],
                    $order
                );
            } catch (\Throwable $e) {
                error_log('Pending-payment email failed for ' . ($order['orderNumber'] ?? '') . ': ' . $e->getMessage());
            }
        }

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

        $payload = $this->checkoutPayload($request);
        $checkout = $isGuest
            ? $this->orders->prepareGuestCheckout($payload)
            : $this->orders->prepareCheckoutFromCart($userId, $payload);

        $paypalOrder = $this->paypal->createOrder($checkout);
        $paypalOrderId = (string) ($paypalOrder['id'] ?? '');

        $pendingOrder = null;
        $pendingEmailSent = false;
        $pendingEmailError = null;

        if ($paypalOrderId !== '') {
            $pendingPayload = [
                ...$payload,
                'status' => 'pending',
                'paypal_order_id' => $paypalOrderId,
            ];

            try {
                if ($isGuest) {
                    $pendingOrder = $this->orders->createFromGuestCart($pendingPayload, $payload['items'] ?? []);
                } else {
                    $pendingOrder = $this->orders->createFromCart($userId, $pendingPayload);
                }

                try {
                    $this->email->sendPaymentPendingEmail(
                        $pendingOrder['customerEmail'],
                        $pendingOrder['customerName'],
                        array_merge($pendingOrder, [
                            'paymentMethod' => $payload['payment_method'] ?? 'paypal',
                            'paymentMethodLabel' => $payload['payment_method_label'] ?: 'PayPal',
                        ])
                    );
                    $pendingEmailSent = true;
                } catch (\Throwable $e) {
                    $pendingEmailError = $e->getMessage();
                    error_log('Pending-order email failed for PayPal order ' . $paypalOrderId . ': ' . $e->getMessage());
                }
            } catch (\Throwable $e) {
                error_log('Pending-order creation failed for PayPal order ' . $paypalOrderId . ': ' . $e->getMessage());
            }
        }

        return [
            ...$paypalOrder,
            'currencyCode' => $this->paypal->currency(),
            'pendingOrder' => $pendingOrder,
            'pendingEmailSent' => $pendingEmailSent,
            'pendingEmailError' => $pendingEmailError,
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

        $resolvedStatus = $this->resolveOrderStatus($paypalOrder);
        $payload = $this->checkoutPayload($request);

        $existingOrder = $this->orders->findByPayPalOrderId($paypalOrderId);
        $wasAlreadyPaid = ($existingOrder['status'] ?? '') === 'paid';

        if ($wasAlreadyPaid) {
            $resolvedStatus = 'paid';
        }

        if ($isGuest) {
            if ($existingOrder) {
                // Reuse the pending order created in create() — do NOT insert
                // a duplicate guest order on capture.
                if (($existingOrder['status'] ?? '') !== $resolvedStatus) {
                    $this->orders->updateStatus((int) $existingOrder['id'], $resolvedStatus);
                }
                $order = $this->orders->findByPayPalOrderId($paypalOrderId) ?? $existingOrder;
            } else {
                $order = $this->orders->createFromGuestCart([
                    ...$payload,
                    'status' => $resolvedStatus,
                    'paypal_order_id' => $paypalOrderId,
                ], $payload['items'] ?? []);
            }
        } else {
            if ($existingOrder) {
                $this->orders->updateStatus((int) $existingOrder['id'], $resolvedStatus);
                $order = $this->orders->findByPayPalOrderId($paypalOrderId) ?? $existingOrder;
            } else {
                $order = $this->orders->createFromCart($userId, [
                    ...$payload,
                    'status' => $resolvedStatus,
                    'paypal_order_id' => $paypalOrderId,
                ]);
            }
        }

        // Send appropriate email based on payment status.
        // Only send the final confirmation after successful payment.
        $order['paymentMethod'] = $payload['payment_method'] ?? 'paypal';
        $order['paymentMethodLabel'] = $payload['payment_method_label'] ?: 'PayPal';
        try {
            if ($resolvedStatus === 'paid' && ($existingOrder['status'] ?? 'pending') !== 'paid') {
                $this->email->sendPaymentConfirmedEmail(
                    $order['customerEmail'],
                    $order['customerName'],
                    $order
                );
            }
        } catch (\Throwable $e) {
            error_log('Order confirmation email failed for ' . ($order['orderNumber'] ?? '') . ': ' . $e->getMessage());
        }

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

    public function paypalWebhook(Request $request): array
    {
        $payload = $request->getParsedBody();
        $eventType = $payload['event_type'] ?? '';

        // Only process payment-related events
        if (!in_array($eventType, [
            'CHECKOUT.ORDER.APPROVED',
            'PAYMENT.CAPTURE.COMPLETED',
            'PAYMENT.CAPTURE.DENIED',
            'PAYMENT.CAPTURE.REFUNDED',
            'PAYMENT.CAPTURE.PENDING',
            'CHECKOUT.ORDER.COMPLETED',
        ], true)) {
            return ['received' => true, 'processed' => false];
        }

        $resource = $payload['resource'] ?? [];
        $paypalOrderId = $resource['id'] ?? ($resource['supplementary_data'] ?? [])['related_ids'] ?? ['order_id' => ''];
        if (is_array($paypalOrderId)) {
            $paypalOrderId = $paypalOrderId['order_id'] ?? '';
        }

        if (empty($paypalOrderId)) {
            return ['received' => true, 'processed' => false, 'error' => 'No order ID in webhook'];
        }

        try {
            $paypalOrder = $this->paypal->getOrder($paypalOrderId);
            $newStatus = $this->resolveOrderStatus($paypalOrder);

            // Find order by PayPal order ID
            $orders = $this->orders->all();
            $order = null;
            foreach ($orders as $o) {
                if (($o['paypalOrderId'] ?? '') === $paypalOrderId) {
                    $order = $o;
                    break;
                }
            }

            if (!$order) {
                return ['received' => true, 'processed' => false, 'error' => 'Order not found'];
            }

            $currentStatus = $order['status'] ?? 'pending';

            // Only update if status changed
            if ($newStatus !== $currentStatus) {
                $this->orders->updateStatus((int) $order['id'], $newStatus);

                if ($currentStatus !== 'paid' && $newStatus === 'paid') {
                    try {
                        $this->email->sendPaymentConfirmedEmail(
                            $order['customerEmail'],
                            $order['customerName'],
                            array_merge($order, ['status' => $newStatus])
                        );
                    } catch (\Throwable $e) {
                        error_log('Webhook confirmation email failed for ' . ($order['orderNumber'] ?? '') . ': ' . $e->getMessage());
                    }
                }
            }

            return ['received' => true, 'processed' => true, 'status' => $newStatus];
        } catch (\Throwable $e) {
            return ['received' => true, 'processed' => false, 'error' => $e->getMessage()];
        }
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
            'payment_method' => (string) ($request->input('paymentMethod') ?? $request->input('payment_method') ?? 'paypal'),
            'payment_method_label' => (string) ($request->input('paymentMethodLabel') ?? $request->input('payment_method_label') ?? 'PayPal'),
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