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

        if (in_array($role, self::MANAGER_ROLES, true)) {
            $orders = $this->orders->all();
        } else {
            // Include guest rows placed with the same email so pending
            // "waiting payment" orders created before login are visible too.
            $orders = $this->orders->allByUserIncludingEmail($userId, (string) ($user['email'] ?? ''));
        }

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
            'discount_code' => strtoupper(trim((string) ($request->input('discount_code') ?? $request->input('discountCode') ?? ''))) ?: null,
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
                'discount' => $payload['discount'],
                'discount_code' => $payload['discount_code'],
                'items' => $payload['items'],
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

        // Enforce the 1-hour payment window at capture time as well.
        if ($existingOrder && !$wasAlreadyPaid) {
            $expired = $this->orders->expireOrderIfOverdue($existingOrder, 1);

            if ($expired !== null) {
                throw new RuntimeException('Payment time expired. This order was automatically cancelled and the items were returned to stock. Please place a new order.', 410);
            }
        }

        if ($isGuest) {
            if ($existingOrder) {
                // Reuse the pending order created in create() — do NOT insert
                // a duplicate guest order on capture.
                if (($existingOrder['status'] ?? '') !== $resolvedStatus) {
                    $this->orders->updateStatus((int) $existingOrder['id'], $resolvedStatus);
                }
                $this->orders->consumeWelcomeVoucherForOrder((int) $existingOrder['id']);
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
                // Pending order was created as guest (or by another session):
                // attach it to this customer so it stays in My Orders.
                if (($existingOrder['userId'] ?? null) === null) {
                    $this->orders->claimOrderForUser((int) $existingOrder['id'], $userId);
                }
                $this->orders->updateStatus((int) $existingOrder['id'], $resolvedStatus);
                $this->orders->consumeWelcomeVoucherForOrder((int) $existingOrder['id']);
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

    /**
     * Admin fulfillment update: PATCH /api/orders/{id} (manager/admin only).
     *
     * Flow: paid -> processing (confirm products) -> packed ->
     * shipped (out for delivery, requires courier + tracking number) -> delivered.
     */
    public function update(Request $request): array
    {
        $orderId = (int) ($request->attribute('id') ?? $request->attribute('orderId') ?? 0);

        if ($orderId <= 0) {
            throw new RuntimeException('Order id is required.', 400);
        }

        $order = $this->orders->findByIdAny($orderId);

        if (!$order) {
            throw new RuntimeException('Order not found.', 404);
        }

        $currentStatus = (string) ($order['status'] ?? '');
        $requestedStatus = strtolower(trim((string) ($request->input('status') ?? $currentStatus)));

        $courierInput = $request->input('courier');
        $trackingInput = $request->input('trackingNumber') ?? $request->input('tracking_number');

        // Keep existing fulfillment info when the admin does not send new values.
        $courier = $courierInput !== null ? trim((string) $courierInput) : trim((string) ($order['courier'] ?? ''));
        $trackingNumber = $trackingInput !== null ? trim((string) $trackingInput) : trim((string) ($order['trackingNumber'] ?? ''));

        $allowedStatuses = ['processing', 'packed', 'shipped', 'delivered', 'cancelled'];

        if (!in_array($requestedStatus, $allowedStatuses, true)) {
            throw new RuntimeException('Invalid status. Allowed: ' . implode(', ', $allowedStatuses) . '.', 422);
        }

        $transitions = [
            'paid' => ['processing', 'cancelled'],
            'processing' => ['packed', 'cancelled'],
            'packed' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
            'pending' => [],
        ];

        // Allow re-saving courier/tracking on an already-shipped order.
        $isMetadataOnlyUpdate = $requestedStatus === $currentStatus;

        if (!$isMetadataOnlyUpdate && !in_array($requestedStatus, $transitions[$currentStatus] ?? [], true)) {
            throw new RuntimeException(
                'Cannot change status from "' . $currentStatus . '" to "' . $requestedStatus . '".',
                422
            );
        }

        if ($requestedStatus === 'shipped' && ($courier === '' || $trackingNumber === '')) {
            throw new RuntimeException('Courier and tracking number are required to ship an order.', 422);
        }

        $updated = $this->orders->updateFulfillment(
            $orderId,
            $requestedStatus,
            $courier === '' ? null : $courier,
            $trackingNumber === '' ? null : $trackingNumber
        );

        if ($requestedStatus === 'cancelled') {
            // Admin-cancelled: give the welcome voucher back if this order used it.
            $this->orders->releaseWelcomeVoucherForOrder($orderId);
            $updated = $this->orders->findByIdAny($orderId) ?? $updated;
        }

        // Notify the customer (best effort — never fail the admin action on SMTP errors).
        // Shipped -> email with courier + tracking ID so the customer can track
        // the parcel. Delivered -> email confirming the package has arrived.
        try {
            $mailOrder = array_merge($updated, [
                'tracking_number' => $updated['trackingNumber'] ?? null,
                'tracking_carrier' => $updated['courier'] ?? null,
                'tracking_url' => '',
            ]);

            if ($requestedStatus === 'shipped') {
                $this->email->sendOrderShippedEmail(
                    $updated['customerEmail'],
                    $updated['customerName'],
                    $mailOrder
                );
            } elseif ($requestedStatus === 'delivered') {
                $this->email->sendOrderDeliveredEmail(
                    $updated['customerEmail'],
                    $updated['customerName'],
                    $mailOrder
                );
            } else {
                $this->email->sendShippingUpdateEmail(
                    $updated['customerEmail'],
                    $updated['customerName'],
                    $mailOrder
                );
            }
        } catch (\Throwable $e) {
            error_log('Fulfillment email failed for ' . ($updated['orderNumber'] ?? '') . ': ' . $e->getMessage());
        }

        return ['order' => $updated];
    }

    /**
     * Public resume lookup for the "Pay with PayPal / Card" link in the
     * pending-payment email: GET /api/orders/resume?order=AUB-...
     * The order number is unguessable, so no auth is required (works
     * logged in or not, on any device).
     */
    public function resume(Request $request): array
    {
        $orderNumber = trim((string) $request->queryParam('order', ''));

        if ($orderNumber === '') {
            throw new RuntimeException('Order number is required.', 400);
        }

        $order = $this->orders->findByOrderNumber($orderNumber);

        if (!$order) {
            throw new RuntimeException('Order not found or expired.', 404);
        }

        // Enforce the 1-hour payment window even if the cron has not swept
        // yet: an overdue pending order is cancelled on the spot (stock
        // returned, cancellation email sent).
        $expired = $this->orders->expireOrderIfOverdue($order, 1);

        if ($expired !== null) {
            $order = $expired;
        }

        return [
            'order' => $order,
            'canPay' => ($order['status'] ?? '') === 'pending',
        ];
    }

    /**
     * Attach a fresh PayPal order to an existing pending DB order that has
     * no PayPal id yet (e.g. created via direct checkout): POST
     * /api/orders/{orderNumber}/paypal — public, same reasoning as resume().
     */
    public function createPaypalForExisting(Request $request): array
    {
        $this->ensurePaypalConfigured();

        $orderNumber = trim((string) $request->attribute('orderNumber'));

        if ($orderNumber === '') {
            throw new RuntimeException('Order number is required.', 400);
        }

        $order = $this->orders->findByOrderNumber($orderNumber);

        if (!$order) {
            throw new RuntimeException('Order not found or expired.', 404);
        }

        if (($order['status'] ?? '') !== 'pending') {
            throw new RuntimeException('This order can no longer be paid (status: ' . ($order['status'] ?? 'unknown') . ').', 409);
        }

        $expired = $this->orders->expireOrderIfOverdue($order, 1);

        if ($expired !== null) {
            throw new RuntimeException('Payment time expired. This order was automatically cancelled and the items were returned to stock. Please place a new order.', 410);
        }

        if (!empty($order['paypalOrderId'])) {
            $paypalOrder = $this->paypal->getOrder((string) $order['paypalOrderId']);

            return [
                ...$paypalOrder,
                'currencyCode' => $this->paypal->currency(),
                'pendingOrder' => $order,
                'pendingEmailSent' => false,
            ];
        }

        $checkout = [
            'customer_name' => (string) ($order['customerName'] ?? ''),
            'subtotal' => (float) ($order['subtotal'] ?? 0),
            'discount' => (float) ($order['discount'] ?? 0),
            'shipping' => (float) ($order['shipping'] ?? 0),
            'total' => (float) ($order['total'] ?? 0),
        ];

        $paypalOrder = $this->paypal->createOrder($checkout);
        $paypalOrderId = (string) ($paypalOrder['id'] ?? '');

        if ($paypalOrderId === '') {
            throw new RuntimeException('Could not initiate PayPal checkout for this order.', 502);
        }

        $this->orders->updatePaypalOrderId((int) $order['id'], $paypalOrderId);
        $order = $this->orders->findByOrderNumber($orderNumber) ?? $order;

        return [
            ...$paypalOrder,
            'currencyCode' => $this->paypal->currency(),
            'pendingOrder' => $order,
            'pendingEmailSent' => false,
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

                if ($newStatus === 'paid') {
                    $this->orders->consumeWelcomeVoucherForOrder((int) $order['id']);
                }

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
            'discount_code' => strtoupper(trim((string) ($request->input('discount_code') ?? $request->input('discountCode') ?? ''))) ?: null,
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