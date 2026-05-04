<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\CartRepository;

final class CartController
{
    public function __construct(private readonly CartRepository $carts)
    {
    }

    public function show(Request $request): array
    {
        return $this->carts->cartPayload((int) $request->attribute('user')['id']);
    }

    public function storeItem(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];

        $this->carts->upsertItem($userId, [
            'product_id' => (int) $request->input('product_id'),
            'quantity' => (int) $request->input('quantity', 1),
            'size' => (string) $request->input('size'),
            'color' => (string) $request->input('color'),
        ], $this->normalizedCountry($request->header('X-Customer-Country')));

        return $this->carts->cartPayload($userId);
    }

    public function updateItem(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];
        $this->carts->updateItemQuantity($userId, (int) $request->attribute('id'), (int) $request->input('quantity', 1));

        return $this->carts->cartPayload($userId);
    }

    public function deleteItem(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];
        $this->carts->deleteItem($userId, (int) $request->attribute('id'));

        return $this->carts->cartPayload($userId);
    }

    public function applyDiscount(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];
        $this->carts->applyDiscount($userId, (string) $request->input('code'));

        return $this->carts->cartPayload($userId);
    }

    public function clear(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];
        $this->carts->clear($userId);

        return $this->carts->cartPayload($userId);
    }

    private function normalizedCountry(?string $country): ?string
    {
        $value = trim((string) $country);

        return $value !== '' ? $value : null;
    }
}
