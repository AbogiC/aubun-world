<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class HomeViewSettingsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getSettings(): ?array
    {
        $statement = $this->pdo->query(
            'SELECT id, hero_background_image, hero_kicker, hero_title, hero_copy,
                    hero_primary_button_text, hero_primary_button_link,
                    hero_secondary_button_text, hero_secondary_button_link,
                    featured_title, featured_subtitle
             FROM home_view_settings
             ORDER BY id DESC
             LIMIT 1'
        );
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        $settings = $this->mapSettings($row);
        $settings['featuredItems'] = $this->getFeaturedItems((int) $row['id']);

        return $settings;
    }

    public function getFeaturedItems(int $settingId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, label, route_category, title, eyebrow, description, product_id, sort_order, is_active
             FROM home_view_featured_items
             WHERE home_view_setting_id = :settingId AND is_active = 1
             ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute(['settingId' => $settingId]);

        return array_map(fn (array $row): array => $this->mapFeaturedItem($row), $statement->fetchAll());
    }

    public function create(array $payload): array
    {
        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO home_view_settings (
                    hero_background_image, hero_kicker, hero_title, hero_copy,
                    hero_primary_button_text, hero_primary_button_link,
                    hero_secondary_button_text, hero_secondary_button_link,
                    featured_title, featured_subtitle, created_at, updated_at
                 ) VALUES (
                    :hero_background_image, :hero_kicker, :hero_title, :hero_copy,
                    :hero_primary_button_text, :hero_primary_button_link,
                    :hero_secondary_button_text, :hero_secondary_button_link,
                    :featured_title, :featured_subtitle, NOW(), NOW()
                 )'
            );
            $statement->execute($this->persistedSettings($payload));

            $settingId = (int) $this->pdo->lastInsertId();

            if (!empty($payload['featuredItems']) && is_array($payload['featuredItems'])) {
                foreach ($payload['featuredItems'] as $index => $item) {
                    $this->createFeaturedItem($settingId, $item, $index);
                }
            }

            $this->pdo->commit();

            return $this->getSettings();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update(array $payload): ?array
    {
        $settings = $this->getSettings();

        if (!$settings) {
            return $this->create($payload);
        }

        $settingId = $settings['id'];

        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'UPDATE home_view_settings SET
                    hero_background_image = :hero_background_image,
                    hero_kicker = :hero_kicker,
                    hero_title = :hero_title,
                    hero_copy = :hero_copy,
                    hero_primary_button_text = :hero_primary_button_text,
                    hero_primary_button_link = :hero_primary_button_link,
                    hero_secondary_button_text = :hero_secondary_button_text,
                    hero_secondary_button_link = :hero_secondary_button_link,
                    featured_title = :featured_title,
                    featured_subtitle = :featured_subtitle,
                    updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'id' => $settingId,
                ...$this->persistedSettings($payload),
            ]);

            $this->deleteFeaturedItems($settingId);

            if (!empty($payload['featuredItems']) && is_array($payload['featuredItems'])) {
                foreach ($payload['featuredItems'] as $index => $item) {
                    $this->createFeaturedItem($settingId, $item, $index);
                }
            }

            $this->pdo->commit();

            return $this->getSettings();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function createFeaturedItem(int $settingId, array $item, int $sortOrder): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO home_view_featured_items (
                home_view_setting_id, label, route_category, title, eyebrow, description, product_id, sort_order, is_active, created_at, updated_at
             ) VALUES (
                :setting_id, :label, :route_category, :title, :eyebrow, :description, :product_id, :sort_order, :is_active, NOW(), NOW()
             )'
        );
        $statement->execute([
            'setting_id' => $settingId,
            'label' => $item['label'] ?? '',
            'route_category' => $item['routeCategory'] ?? '',
            'title' => $item['title'] ?? '',
            'eyebrow' => $item['eyebrow'] ?? null,
            'description' => $item['description'] ?? null,
            'product_id' => $item['productId'] ?? null,
            'sort_order' => $item['sortOrder'] ?? $sortOrder,
            'is_active' => isset($item['isActive']) ? (int) $item['isActive'] : 1,
        ]);
    }

    private function deleteFeaturedItems(int $settingId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM home_view_featured_items WHERE home_view_setting_id = :settingId');
        $statement->execute(['settingId' => $settingId]);
    }

    private function persistedSettings(array $payload): array
    {
        return [
            'hero_background_image' => $payload['heroBackgroundImage'] ?? null,
            'hero_kicker' => $payload['heroKicker'] ?? null,
            'hero_title' => $payload['heroTitle'] ?? null,
            'hero_copy' => $payload['heroCopy'] ?? null,
            'hero_primary_button_text' => $payload['heroPrimaryButtonText'] ?? null,
            'hero_primary_button_link' => $payload['heroPrimaryButtonLink'] ?? null,
            'hero_secondary_button_text' => $payload['heroSecondaryButtonText'] ?? null,
            'hero_secondary_button_link' => $payload['heroSecondaryButtonLink'] ?? null,
            'featured_title' => $payload['featuredTitle'] ?? null,
            'featured_subtitle' => $payload['featuredSubtitle'] ?? null,
        ];
    }

    private function mapSettings(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'heroBackgroundImage' => $row['hero_background_image'],
            'heroKicker' => $row['hero_kicker'],
            'heroTitle' => $row['hero_title'],
            'heroCopy' => $row['hero_copy'],
            'heroPrimaryButtonText' => $row['hero_primary_button_text'],
            'heroPrimaryButtonLink' => $row['hero_primary_button_link'],
            'heroSecondaryButtonText' => $row['hero_secondary_button_text'],
            'heroSecondaryButtonLink' => $row['hero_secondary_button_link'],
            'featuredTitle' => $row['featured_title'],
            'featuredSubtitle' => $row['featured_subtitle'],
        ];
    }

    private function mapFeaturedItem(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'label' => $row['label'],
            'routeCategory' => $row['route_category'],
            'title' => $row['title'],
            'eyebrow' => $row['eyebrow'],
            'description' => $row['description'],
            'productId' => $row['product_id'] !== null ? (int) $row['product_id'] : null,
            'sortOrder' => (int) $row['sort_order'],
            'isActive' => (bool) $row['is_active'],
        ];
    }
}