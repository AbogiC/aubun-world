<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;
use Throwable;

final class ShippingRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function settings(): array
    {
        return [
            'shopCountries' => $this->shopCountries(),
            'shippingMappings' => $this->shippingMappings(),
        ];
    }

    public function shippingOptionsForCountry(string $countryName): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                scm.id,
                scm.shop_country_id,
                sc.country_name AS shop_country_name,
                scm.continent_name,
                scm.destination_country_name
             FROM shipping_country_mapping scm
             JOIN shop_country sc ON sc.id = scm.shop_country_id
             WHERE LOWER(scm.destination_country_name) = LOWER(:country_name)
               AND scm.is_active = 1
             LIMIT 1'
        );
        $statement->execute([
            'country_name' => $countryName,
        ]);
        $mapping = $statement->fetch();

        if (!$mapping) {
            return null;
        }

        return [
            'mappingId' => (int) $mapping['id'],
            'shopCountryId' => (int) $mapping['shop_country_id'],
            'shopCountryName' => $mapping['shop_country_name'],
            'continentName' => $mapping['continent_name'],
            'destinationCountryName' => $mapping['destination_country_name'],
            'shippingRates' => $this->shippingRatesByMappingIds([(int) $mapping['id']])[(int) $mapping['id']] ?? [],
        ];
    }

    public function shippingRateForCountry(string $countryName, int $shippingRateId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                scm.id AS mapping_id,
                scm.destination_country_name,
                sc.country_name AS shop_country_name,
                srt.id AS shipping_rate_id,
                srt.tier_name,
                srt.min_distance_km,
                srt.max_distance_km,
                srt.shipping_cost
             FROM shipping_country_mapping scm
             JOIN shop_country sc ON sc.id = scm.shop_country_id
             JOIN shipping_rate_tiers srt ON srt.shipping_country_mapping_id = scm.id
             WHERE LOWER(scm.destination_country_name) = LOWER(:country_name)
               AND scm.is_active = 1
               AND srt.id = :shipping_rate_id
             LIMIT 1'
        );
        $statement->execute([
            'country_name' => $countryName,
            'shipping_rate_id' => $shippingRateId,
        ]);
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return [
            'mappingId' => (int) $row['mapping_id'],
            'destinationCountryName' => $row['destination_country_name'],
            'shopCountryName' => $row['shop_country_name'],
            'shippingRate' => [
                'id' => (int) $row['shipping_rate_id'],
                'tierName' => $row['tier_name'],
                'minDistanceKm' => (float) $row['min_distance_km'],
                'maxDistanceKm' => $row['max_distance_km'] !== null ? (float) $row['max_distance_km'] : null,
                'shippingCost' => (float) $row['shipping_cost'],
            ],
        ];
    }

    public function createShopCountry(string $countryName): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO shop_country (country_name, created_at, updated_at)
             VALUES (:country_name, NOW(), NOW())'
        );
        $statement->execute([
            'country_name' => $countryName,
        ]);

        return $this->findShopCountry((int) $this->pdo->lastInsertId())
            ?? throw new RuntimeException('Unable to load saved shop country.', 500);
    }

    public function deleteShopCountry(int $id): bool
    {
        $usageStatement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM shipping_country_mapping WHERE shop_country_id = :id'
        );
        $usageStatement->execute(['id' => $id]);

        if ((int) $usageStatement->fetchColumn() > 0) {
            throw new RuntimeException('This shop country is still linked to shipping mappings.', 422);
        }

        $statement = $this->pdo->prepare('DELETE FROM shop_country WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function replaceShippingMappings(array $mappings): array
    {
        try {
            $this->pdo->beginTransaction();

            $this->pdo->exec('DELETE FROM shipping_rate_tiers');
            $this->pdo->exec('DELETE FROM shipping_country_mapping');

            if ($mappings !== []) {
                $insertMapping = $this->pdo->prepare(
                    'INSERT INTO shipping_country_mapping (
                        shop_country_id, continent_name, destination_country_name, is_active, created_at, updated_at
                     ) VALUES (
                        :shop_country_id, :continent_name, :destination_country_name, :is_active, NOW(), NOW()
                     )'
                );
                $insertTier = $this->pdo->prepare(
                    'INSERT INTO shipping_rate_tiers (
                        shipping_country_mapping_id, tier_name, min_distance_km, max_distance_km, shipping_cost, sort_order, created_at, updated_at
                     ) VALUES (
                        :shipping_country_mapping_id, :tier_name, :min_distance_km, :max_distance_km, :shipping_cost, :sort_order, NOW(), NOW()
                     )'
                );

                foreach ($mappings as $mapping) {
                    $insertMapping->execute([
                        'shop_country_id' => $mapping['shopCountryId'],
                        'continent_name' => $mapping['continentName'],
                        'destination_country_name' => $mapping['destinationCountryName'],
                        'is_active' => $mapping['isActive'] ? 1 : 0,
                    ]);

                    $mappingId = (int) $this->pdo->lastInsertId();

                    foreach ($mapping['shippingRates'] as $index => $rate) {
                        $insertTier->execute([
                            'shipping_country_mapping_id' => $mappingId,
                            'tier_name' => $rate['tierName'],
                            'min_distance_km' => $rate['minDistanceKm'],
                            'max_distance_km' => $rate['maxDistanceKm'],
                            'shipping_cost' => $rate['shippingCost'],
                            'sort_order' => $index,
                        ]);
                    }
                }
            }

            $this->pdo->commit();

            return $this->settings();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function shopCountryExists(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM shop_country WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return (bool) $statement->fetchColumn();
    }

    public function shopCountryNameExists(string $countryName): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT id FROM shop_country WHERE LOWER(country_name) = LOWER(:country_name) LIMIT 1'
        );
        $statement->execute(['country_name' => $countryName]);

        return (bool) $statement->fetchColumn();
    }

    private function shopCountries(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, country_name FROM shop_country ORDER BY country_name ASC'
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'countryName' => $row['country_name'],
            ],
            $statement->fetchAll()
        );
    }

    private function shippingMappings(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                scm.id,
                scm.shop_country_id,
                sc.country_name AS shop_country_name,
                scm.continent_name,
                scm.destination_country_name,
                scm.is_active
             FROM shipping_country_mapping scm
             JOIN shop_country sc ON sc.id = scm.shop_country_id
             ORDER BY scm.continent_name ASC, scm.destination_country_name ASC'
        );
        $mappings = $statement->fetchAll();

        if ($mappings === []) {
            return [];
        }

        $mappingIds = array_map(
            static fn (array $mapping): int => (int) $mapping['id'],
            $mappings
        );
        $tiersByMappingId = $this->shippingRatesByMappingIds($mappingIds);

        return array_map(
            static fn (array $mapping): array => [
                'id' => (int) $mapping['id'],
                'shopCountryId' => (int) $mapping['shop_country_id'],
                'shopCountryName' => $mapping['shop_country_name'],
                'continentName' => $mapping['continent_name'],
                'destinationCountryName' => $mapping['destination_country_name'],
                'isActive' => (bool) $mapping['is_active'],
                'shippingRates' => $tiersByMappingId[(int) $mapping['id']] ?? [],
            ],
            $mappings
        );
    }

    private function shippingRatesByMappingIds(array $mappingIds): array
    {
        if ($mappingIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($mappingIds), '?'));
        $statement = $this->pdo->prepare(
            "SELECT id, shipping_country_mapping_id, tier_name, min_distance_km, max_distance_km, shipping_cost
             FROM shipping_rate_tiers
             WHERE shipping_country_mapping_id IN ($placeholders)
             ORDER BY shipping_country_mapping_id ASC, sort_order ASC, id ASC"
        );
        $statement->execute($mappingIds);

        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $mappingId = (int) $row['shipping_country_mapping_id'];
            $grouped[$mappingId] ??= [];
            $grouped[$mappingId][] = [
                'id' => (int) $row['id'],
                'tierName' => $row['tier_name'],
                'minDistanceKm' => (float) $row['min_distance_km'],
                'maxDistanceKm' => $row['max_distance_km'] !== null ? (float) $row['max_distance_km'] : null,
                'shippingCost' => (float) $row['shipping_cost'],
            ];
        }

        return $grouped;
    }

    private function findShopCountry(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, country_name FROM shop_country WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'countryName' => $row['country_name'],
        ];
    }
}
