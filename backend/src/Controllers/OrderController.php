<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;

final class OrderController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly CartRepository $carts
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
        ]);

        return [
            'message' => 'Order placed successfully.',
            'order' => $order,
            ...$this->carts->cartPayload($userId),
        ];
    }
}
