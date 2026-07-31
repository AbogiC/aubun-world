<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MixMatchConfigRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array{key: string, label: string, icon: string, scope: string, position: string, categories: array<int, string>}>
     */
    public function slots(): array
    {
        $slots = $this->pdo
            ->query('SELECT slot_key, label, icon, scope, position FROM mix_match_slots ORDER BY sort_order ASC, id ASC')
            ->fetchAll();

        $categoriesBySlot = [];

        $rows = $this->pdo
            ->query(
                'SELECT mmsc.slot_id, mms.slot_key, mmsc.category
                 FROM mix_match_slot_categories mmsc
                 JOIN mix_match_slots mms ON mms.id = mmsc.slot_id
                 ORDER BY mms.sort_order ASC, mmsc.sort_order ASC'
            )
            ->fetchAll();

        foreach ($rows as $row) {
            $categoriesBySlot[$row['slot_key']][] = $row['category'];
        }

        return array_map(static fn (array $slot): array => [
            'key' => $slot['slot_key'],
            'label' => $slot['label'],
            'icon' => $slot['icon'],
            'scope' => $slot['scope'],
            'position' => $slot['position'],
            'categories' => $categoriesBySlot[$slot['slot_key']] ?? [],
        ], $slots);
    }

    /**
     * @return array<int, array{name: string, icon: string, blurb: string, slots: array<string, array<int, string>>}>
     */
    public function presets(): array
    {
        $presets = $this->pdo
            ->query('SELECT id, preset_key, name, icon, blurb FROM mix_match_presets ORDER BY sort_order ASC, id ASC')
            ->fetchAll();

        $categoriesByPreset = [];

        $rows = $this->pdo
            ->query(
                'SELECT preset_id, slot_key, category
                 FROM mix_match_preset_categories
                 ORDER BY sort_order ASC, id ASC'
            )
            ->fetchAll();

        foreach ($rows as $row) {
            $presetId = (int) $row['preset_id'];
            $categoriesByPreset[$presetId][$row['slot_key']][] = $row['category'];
        }

        return array_map(static function (array $preset) use ($categoriesByPreset): array {
            return [
                'name' => $preset['name'],
                'icon' => $preset['icon'],
                'blurb' => $preset['blurb'],
                'slots' => $categoriesByPreset[(int) $preset['id']] ?? [],
            ];
        }, $presets);
    }
}
