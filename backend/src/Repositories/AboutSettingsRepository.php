<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final class AboutSettingsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function ensureSchema(): void
    {
        try {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS about_settings (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    hero_kicker VARCHAR(120) NULL,
                    hero_title VARCHAR(190) NULL,
                    hero_subtitle TEXT NULL,
                    mission_kicker VARCHAR(120) NULL,
                    mission_title VARCHAR(190) NULL,
                    mission_lead TEXT NULL,
                    mission_body1 TEXT NULL,
                    mission_body2 TEXT NULL,
                    mission_image_url VARCHAR(500) NULL,
                    values_title VARCHAR(190) NULL,
                    values_subtitle TEXT NULL,
                    team_title VARCHAR(190) NULL,
                    team_subtitle TEXT NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )'
            );

            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS about_values (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    about_setting_id INT UNSIGNED NOT NULL,
                    icon VARCHAR(120) NULL,
                    title VARCHAR(190) NOT NULL,
                    description TEXT NULL,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT fk_about_values_setting FOREIGN KEY (about_setting_id) REFERENCES about_settings(id) ON DELETE CASCADE
                )'
            );

            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS about_team_members (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    about_setting_id INT UNSIGNED NOT NULL,
                    name VARCHAR(190) NOT NULL,
                    role VARCHAR(190) NULL,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT fk_about_team_setting FOREIGN KEY (about_setting_id) REFERENCES about_settings(id) ON DELETE CASCADE
                )'
            );
        } catch (Throwable) {
            // Ignore — tables may already exist or DB user lacks DDL rights.
        }
    }

    public function getSettings(): ?array
    {
        $this->ensureSchema();

        $statement = $this->pdo->query(
            'SELECT id, hero_kicker, hero_title, hero_subtitle,
                    mission_kicker, mission_title, mission_lead,
                    mission_body1, mission_body2, mission_image_url,
                    values_title, values_subtitle,
                    team_title, team_subtitle
             FROM about_settings
             ORDER BY id DESC
             LIMIT 1'
        );

        $row = $statement ? $statement->fetch() : false;

        if (!$row) {
            return null;
        }

        $settings = $this->mapSettings($row);
        $settings['values'] = $this->getValues((int) $row['id']);
        $settings['team'] = $this->getTeam((int) $row['id']);

        return $settings;
    }

    public function getValues(int $settingId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, icon, title, description, sort_order, is_active
             FROM about_values
             WHERE about_setting_id = :settingId
             ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute(['settingId' => $settingId]);

        return array_map(fn (array $row): array => $this->mapValue($row), $statement->fetchAll());
    }

    public function getTeam(int $settingId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, role, sort_order, is_active
             FROM about_team_members
             WHERE about_setting_id = :settingId
             ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute(['settingId' => $settingId]);

        return array_map(fn (array $row): array => $this->mapTeamMember($row), $statement->fetchAll());
    }

    public function create(array $payload): array
    {
        $this->ensureSchema();
        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO about_settings (
                    hero_kicker, hero_title, hero_subtitle,
                    mission_kicker, mission_title, mission_lead,
                    mission_body1, mission_body2, mission_image_url,
                    values_title, values_subtitle,
                    team_title, team_subtitle,
                    created_at, updated_at
                 ) VALUES (
                    :hero_kicker, :hero_title, :hero_subtitle,
                    :mission_kicker, :mission_title, :mission_lead,
                    :mission_body1, :mission_body2, :mission_image_url,
                    :values_title, :values_subtitle,
                    :team_title, :team_subtitle,
                    NOW(), NOW()
                 )'
            );
            $statement->execute($this->persistedSettings($payload));

            $settingId = (int) $this->pdo->lastInsertId();

            $this->replaceValues($settingId, $payload['values'] ?? []);
            $this->replaceTeam($settingId, $payload['team'] ?? []);

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
                'UPDATE about_settings SET
                    hero_kicker = :hero_kicker,
                    hero_title = :hero_title,
                    hero_subtitle = :hero_subtitle,
                    mission_kicker = :mission_kicker,
                    mission_title = :mission_title,
                    mission_lead = :mission_lead,
                    mission_body1 = :mission_body1,
                    mission_body2 = :mission_body2,
                    mission_image_url = :mission_image_url,
                    values_title = :values_title,
                    values_subtitle = :values_subtitle,
                    team_title = :team_title,
                    team_subtitle = :team_subtitle,
                    updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'id' => $settingId,
                ...$this->persistedSettings($payload),
            ]);

            $this->replaceValues($settingId, $payload['values'] ?? []);
            $this->replaceTeam($settingId, $payload['team'] ?? []);

            $this->pdo->commit();

            return $this->getSettings();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function replaceValues(int $settingId, mixed $values): void
    {
        $statement = $this->pdo->prepare('DELETE FROM about_values WHERE about_setting_id = :settingId');
        $statement->execute(['settingId' => $settingId]);

        if (!is_array($values)) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO about_values (
                about_setting_id, icon, title, description, sort_order, is_active, created_at, updated_at
             ) VALUES (
                :setting_id, :icon, :title, :description, :sort_order, :is_active, NOW(), NOW()
             )'
        );

        foreach (array_values($values) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $insert->execute([
                'setting_id' => $settingId,
                'icon' => isset($item['icon']) && trim((string) $item['icon']) !== '' ? mb_substr(trim((string) $item['icon']), 0, 120) : null,
                'title' => mb_substr($title, 0, 190),
                'description' => isset($item['description']) && trim((string) $item['description']) !== '' ? (string) $item['description'] : null,
                'sort_order' => isset($item['sortOrder']) ? max(0, (int) $item['sortOrder']) : $index,
                'is_active' => isset($item['isActive']) ? (int) (bool) $item['isActive'] : 1,
            ]);
        }
    }

    private function replaceTeam(int $settingId, mixed $team): void
    {
        $statement = $this->pdo->prepare('DELETE FROM about_team_members WHERE about_setting_id = :settingId');
        $statement->execute(['settingId' => $settingId]);

        if (!is_array($team)) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO about_team_members (
                about_setting_id, name, role, sort_order, is_active, created_at, updated_at
             ) VALUES (
                :setting_id, :name, :role, :sort_order, :is_active, NOW(), NOW()
             )'
        );

        foreach (array_values($team) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $insert->execute([
                'setting_id' => $settingId,
                'name' => mb_substr($name, 0, 190),
                'role' => isset($item['role']) && trim((string) $item['role']) !== '' ? mb_substr(trim((string) $item['role']), 0, 190) : null,
                'sort_order' => isset($item['sortOrder']) ? max(0, (int) $item['sortOrder']) : $index,
                'is_active' => isset($item['isActive']) ? (int) (bool) $item['isActive'] : 1,
            ]);
        }
    }

    private function persistedSettings(array $payload): array
    {
        return [
            'hero_kicker' => $payload['heroKicker'] ?? null,
            'hero_title' => $payload['heroTitle'] ?? null,
            'hero_subtitle' => $payload['heroSubtitle'] ?? null,
            'mission_kicker' => $payload['missionKicker'] ?? null,
            'mission_title' => $payload['missionTitle'] ?? null,
            'mission_lead' => $payload['missionLead'] ?? null,
            'mission_body1' => $payload['missionBody1'] ?? null,
            'mission_body2' => $payload['missionBody2'] ?? null,
            'mission_image_url' => $payload['missionImageUrl'] ?? null,
            'values_title' => $payload['valuesTitle'] ?? null,
            'values_subtitle' => $payload['valuesSubtitle'] ?? null,
            'team_title' => $payload['teamTitle'] ?? null,
            'team_subtitle' => $payload['teamSubtitle'] ?? null,
        ];
    }

    private function mapSettings(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'heroKicker' => $row['hero_kicker'],
            'heroTitle' => $row['hero_title'],
            'heroSubtitle' => $row['hero_subtitle'],
            'missionKicker' => $row['mission_kicker'],
            'missionTitle' => $row['mission_title'],
            'missionLead' => $row['mission_lead'],
            'missionBody1' => $row['mission_body1'],
            'missionBody2' => $row['mission_body2'],
            'missionImageUrl' => $row['mission_image_url'],
            'valuesTitle' => $row['values_title'],
            'valuesSubtitle' => $row['values_subtitle'],
            'teamTitle' => $row['team_title'],
            'teamSubtitle' => $row['team_subtitle'],
        ];
    }

    private function mapValue(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'icon' => $row['icon'],
            'title' => $row['title'],
            'description' => $row['description'],
            'sortOrder' => (int) $row['sort_order'],
            'isActive' => (bool) $row['is_active'],
        ];
    }

    private function mapTeamMember(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'role' => $row['role'],
            'sortOrder' => (int) $row['sort_order'],
            'isActive' => (bool) $row['is_active'],
        ];
    }
}
