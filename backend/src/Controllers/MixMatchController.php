<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\CartRepository;
use App\Repositories\MixMatchRepository;
use App\Services\MixMatchService;
use RuntimeException;

final class MixMatchController
{
    public function __construct(
        private readonly MixMatchService $mixMatch,
        private readonly MixMatchRepository $looks,
        private readonly CartRepository $carts
    ) {
    }

    public function config(Request $request): array
    {
        return $this->mixMatch->config();
    }

    public function look(Request $request): array
    {
        $pieces = $this->mixMatch->normalizePieces($request->input('pieces'));

        return [
            'look' => $this->mixMatch->resolveLook(
                $pieces,
                $this->normalizedCountry($request->header('X-Customer-Country'))
            ),
        ];
    }

    public function index(Request $request): array
    {
        return [
            'looks' => $this->looks->allForUser(
                (int) $request->attribute('user')['id'],
                $this->normalizedCountry($request->header('X-Customer-Country'))
            ),
        ];
    }

    public function show(Request $request): array
    {
        $look = $this->looks->find(
            (int) $request->attribute('id'),
            (int) $request->attribute('user')['id'],
            $this->normalizedCountry($request->header('X-Customer-Country'))
        );

        if (!$look) {
            throw new RuntimeException('Look not found.', 404);
        }

        return ['look' => $look];
    }

    public function store(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];
        $country = $this->normalizedCountry($request->header('X-Customer-Country'));
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            throw new RuntimeException('Look name is required.', 422);
        }

        $pieces = $this->mixMatch->normalizePieces($request->input('pieces'));
        $this->mixMatch->resolveLook($pieces, $country);

        return [
            'message' => 'Look saved successfully.',
            'look' => $this->looks->create($userId, $name, $pieces),
            'status' => 201,
        ];
    }

    public function update(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];
        $id = (int) $request->attribute('id');
        $country = $this->normalizedCountry($request->header('X-Customer-Country'));
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            throw new RuntimeException('Look name is required.', 422);
        }

        $pieces = $this->mixMatch->normalizePieces($request->input('pieces'));
        $this->mixMatch->resolveLook($pieces, $country);

        $look = $this->looks->update($id, $userId, $name, $pieces);

        if (!$look) {
            throw new RuntimeException('Look not found.', 404);
        }

        return [
            'message' => 'Look updated successfully.',
            'look' => $look,
        ];
    }

    public function destroy(Request $request): array
    {
        $deleted = $this->looks->delete(
            (int) $request->attribute('id'),
            (int) $request->attribute('user')['id']
        );

        if (!$deleted) {
            throw new RuntimeException('Look not found.', 404);
        }

        return [
            'message' => 'Look deleted successfully.',
        ];
    }

    public function addToCart(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];
        $country = $this->normalizedCountry($request->header('X-Customer-Country'));

        $look = $this->looks->find(
            (int) $request->attribute('id'),
            $userId,
            $country
        );

        if (!$look) {
            throw new RuntimeException('Look not found.', 404);
        }

        foreach ($look['pieces'] as $piece) {
            $this->carts->upsertItem($userId, [
                'product_id' => $piece['productId'],
                'quantity' => 1,
                'size' => $piece['size'],
                'color' => $piece['color'],
            ], $country);
        }

        return $this->carts->cartPayload($userId);
    }

    private function normalizedCountry(?string $country): ?string
    {
        $value = trim((string) $country);

        return $value !== '' ? $value : null;
    }
}
