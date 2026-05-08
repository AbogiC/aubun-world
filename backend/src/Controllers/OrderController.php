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
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly CartRepository $carts,
        private readonly PayPalOrderService $paypal
    ) {
    }

    public function index(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];

        return [
            'orders' => $this->orders->allByUser($userId),
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
        $order = $this->orders->createFromCart($userId, $this->checkoutPayload($request));

        return [
            'message' => 'Order placed successfully.',
            'order' => $order,
            'paypalOrder' => $capture,
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
}
