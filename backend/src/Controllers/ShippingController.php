<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\ShippingRepository;
use RuntimeException;

final class ShippingController
{
    private const MANAGER_ROLES = ['manager', 'admin'];

    public function __construct(private readonly ShippingRepository $shipping)
    {
    }

    public function index(Request $request): array
    {
        $this->assertManagerAccess($request);

        return $this->shipping->settings();
    }

    public function options(Request $request): array
    {
        $countryName = trim((string) $request->queryParam('country'));

        if ($countryName === '') {
            throw new RuntimeException('Country is required.', 422);
        }

        $shippingOptions = $this->shipping->shippingOptionsForCountry($countryName);

        if ($shippingOptions === null) {
            return [
                'available' => false,
                'country' => $countryName,
                'shippingOptions' => [],
            ];
        }

        return [
            'available' => true,
            'country' => $shippingOptions['destinationCountryName'],
            'shopCountryName' => $shippingOptions['shopCountryName'],
            'shippingOptions' => $shippingOptions['shippingRates'],
        ];
    }

    public function storeShopCountry(Request $request): array
    {
        $this->assertManagerAccess($request);
        $countryName = trim((string) $request->input('countryName'));

        if ($countryName === '') {
            throw new RuntimeException('Shop country is required.', 422);
        }

        if ($this->shipping->shopCountryNameExists($countryName)) {
            throw new RuntimeException('This shop country already exists.', 422);
        }

        return [
            'message' => 'Shop country added successfully.',
            'shopCountry' => $this->shipping->createShopCountry($countryName),
            'status' => 201,
        ];
    }

    public function destroyShopCountry(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        if (!$this->shipping->deleteShopCountry($id)) {
            throw new RuntimeException('Shop country not found.', 404);
        }

        return [
            'message' => 'Shop country deleted successfully.',
        ];
    }

    public function syncMappings(Request $request): array
    {
        $this->assertManagerAccess($request);
        $mappings = $this->validatedMappings($request->input('shippingMappings', []));

        return [
            'message' => 'Shipping mappings saved successfully.',
            ...$this->shipping->replaceShippingMappings($mappings),
        ];
    }

    private function validatedMappings(mixed $values): array
    {
        if (!is_array($values)) {
            throw new RuntimeException('Shipping mappings payload is invalid.', 422);
        }

        $mappings = [];
        $seenDestinations = [];

        foreach ($values as $mapping) {
            if (!is_array($mapping)) {
                continue;
            }

            $shopCountryId = (int) ($mapping['shopCountryId'] ?? 0);
            $continentName = trim((string) ($mapping['continentName'] ?? ''));
            $destinationCountryName = trim((string) ($mapping['destinationCountryName'] ?? ''));
            $isActive = (bool) ($mapping['isActive'] ?? true);
            $shippingRates = $this->validatedShippingRates($mapping['shippingRates'] ?? []);

            if ($shopCountryId <= 0 || !$this->shipping->shopCountryExists($shopCountryId)) {
                throw new RuntimeException('Each shipping mapping must reference a valid shop country.', 422);
            }

            if ($continentName === '' || $destinationCountryName === '') {
                throw new RuntimeException('Continent and destination country are required for every shipping mapping.', 422);
            }

            $destinationKey = strtolower($destinationCountryName);

            if (isset($seenDestinations[$destinationKey])) {
                throw new RuntimeException('Each destination country can only be mapped once.', 422);
            }

            $mappings[] = [
                'shopCountryId' => $shopCountryId,
                'continentName' => $continentName,
                'destinationCountryName' => $destinationCountryName,
                'isActive' => $isActive,
                'shippingRates' => $shippingRates,
            ];
            $seenDestinations[$destinationKey] = true;
        }

        return $mappings;
    }

    private function validatedShippingRates(mixed $values): array
    {
        if (!is_array($values) || $values === []) {
            throw new RuntimeException('At least one shipping cost tier is required.', 422);
        }

        $rates = [];

        foreach ($values as $rate) {
            if (!is_array($rate)) {
                continue;
            }

            $tierName = trim((string) ($rate['tierName'] ?? ''));
            $minDistance = (float) ($rate['minDistanceKm'] ?? 0);
            $maxDistanceInput = $rate['maxDistanceKm'] ?? null;
            $maxDistance = $maxDistanceInput === null || $maxDistanceInput === ''
                ? null
                : (float) $maxDistanceInput;
            $shippingCost = (float) ($rate['shippingCost'] ?? 0);

            if ($tierName === '') {
                throw new RuntimeException('Shipping tier name is required.', 422);
            }

            if ($minDistance < 0) {
                throw new RuntimeException('Minimum distance cannot be negative.', 422);
            }

            if ($maxDistance !== null && $maxDistance < $minDistance) {
                throw new RuntimeException('Maximum distance must be greater than or equal to minimum distance.', 422);
            }

            if ($shippingCost <= 0) {
                throw new RuntimeException('Shipping cost must be greater than zero.', 422);
            }

            $rates[] = [
                'tierName' => $tierName,
                'minDistanceKm' => $minDistance,
                'maxDistanceKm' => $maxDistance,
                'shippingCost' => $shippingCost,
            ];
        }

        if ($rates === []) {
            throw new RuntimeException('At least one valid shipping cost tier is required.', 422);
        }

        return array_values($rates);
    }

    private function assertManagerAccess(Request $request): void
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('You are not allowed to manage shipping settings.', 403);
        }
    }
}
