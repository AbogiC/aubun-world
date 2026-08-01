<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\StockistRepository;
use RuntimeException;

final class StockistController
{
    private const MANAGER_ROLES = ['manager', 'admin'];

    public function __construct(private readonly StockistRepository $stockists)
    {
    }

    public function index(Request $request): array
    {
        return ['stockists' => $this->stockists->all(false)];
    }

    public function manage(Request $request): array
    {
        return ['stockists' => $this->stockists->all(true)];
    }

    public function show(Request $request): array
    {
        $id = (int) $request->attribute('id');
        $stockist = $this->stockists->find($id);

        if (!$stockist) {
            throw new RuntimeException('Stockist not found.', 404);
        }

        return ['stockist' => $stockist];
    }

    public function store(Request $request): array
    {
        $this->assertManagerAccess($request);
        $payload = $this->validatedPayload($request);
        $stockist = $this->stockists->create($payload);

        return [
            'message' => 'Stockist created successfully.',
            'stockist' => $stockist,
            'status' => 201,
        ];
    }

    public function update(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        if (!$this->stockists->find($id)) {
            throw new RuntimeException('Stockist not found.', 404);
        }

        $payload = $this->validatedPayload($request);

        return [
            'message' => 'Stockist updated successfully.',
            'stockist' => $this->stockists->update($id, $payload),
        ];
    }

    public function destroy(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        if (!$this->stockists->delete($id)) {
            throw new RuntimeException('Stockist not found.', 404);
        }

        return [
            'message' => 'Stockist deleted successfully.',
        ];
    }

    private function validatedPayload(Request $request): array
    {
        $name = trim((string) $request->input('name'));
        $region = trim((string) $request->input('region'));
        $type = trim((string) ($request->input('type') ?? 'Boutique'));
        $icon = trim((string) ($request->input('icon') ?? 'bi bi-shop'));
        $address = trim((string) $request->input('address'));
        $city = trim((string) $request->input('city'));
        $url = trim((string) ($request->input('url') ?? ''));
        $sortOrder = (int) $request->input('sortOrder', 0);
        $isActive = (bool) $request->input('isActive', true);

        if ($name === '') {
            throw new RuntimeException('Stockist name is required.', 422);
        }

        if ($region === '') {
            throw new RuntimeException('Stockist region is required.', 422);
        }

        if ($address === '') {
            throw new RuntimeException('Stockist address is required.', 422);
        }

        if ($city === '') {
            throw new RuntimeException('Stockist city is required.', 422);
        }

        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Stockist url must be a valid URL.', 422);
        }

        return [
            'name' => $name,
            'region' => $region,
            'type' => $type,
            'icon' => $icon,
            'address' => $address,
            'city' => $city,
            'url' => $url,
            'sortOrder' => max(0, $sortOrder),
            'isActive' => $isActive,
        ];
    }

    private function assertManagerAccess(Request $request): void
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('You are not allowed to manage stockists.', 403);
        }
    }
}
