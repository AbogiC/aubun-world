<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\AboutSettingsRepository;
use RuntimeException;

final class AboutSettingsController
{
    private const MANAGER_ROLES = ['manager', 'admin'];

    public function __construct(
        private readonly AboutSettingsRepository $aboutSettings,
    ) {
    }

    public function index(Request $request): array
    {
        $settings = $this->aboutSettings->getSettings();

        if (!$settings) {
            return [
                'settings' => null,
                'values' => [],
                'team' => [],
            ];
        }

        return [
            'settings' => $settings,
            'values' => $settings['values'] ?? [],
            'team' => $settings['team'] ?? [],
        ];
    }

    public function store(Request $request): array
    {
        $this->assertManagerAccess($request);
        $payload = $this->validatedPayload($request);
        $result = $this->aboutSettings->create($payload);

        return [
            'message' => 'About page settings created successfully.',
            'settings' => $result,
            'values' => $result['values'] ?? [],
            'team' => $result['team'] ?? [],
            'status' => 201,
        ];
    }

    public function update(Request $request): array
    {
        $this->assertManagerAccess($request);
        $payload = $this->validatedPayload($request);
        $result = $this->aboutSettings->update($payload);

        if (!$result) {
            throw new RuntimeException('About page settings not found.', 404);
        }

        return [
            'message' => 'About page settings updated successfully.',
            'settings' => $result,
            'values' => $result['values'] ?? [],
            'team' => $result['team'] ?? [],
        ];
    }

    private function validatedPayload(Request $request): array
    {
        $text = static function (mixed $value, int $max = 0): ?string {
            $trimmed = trim((string) ($value ?? ''));
            if ($trimmed === '') {
                return null;
            }
            return $max > 0 ? mb_substr($trimmed, 0, $max) : $trimmed;
        };

        $values = $request->input('values') ?? [];
        if (!is_array($values)) {
            $values = [];
        }

        $validatedValues = [];
        foreach ($values as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $validatedValues[] = [
                'icon' => $text($item['icon'] ?? null, 120) ?? 'bi bi-star-fill',
                'title' => mb_substr($title, 0, 190),
                'description' => $text($item['description'] ?? null),
                'sortOrder' => isset($item['sortOrder']) ? max(0, (int) $item['sortOrder']) : count($validatedValues),
                'isActive' => isset($item['isActive']) ? (bool) $item['isActive'] : true,
            ];
        }

        $team = $request->input('team') ?? [];
        if (!is_array($team)) {
            $team = [];
        }

        $validatedTeam = [];
        foreach ($team as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $validatedTeam[] = [
                'name' => mb_substr($name, 0, 190),
                'role' => $text($item['role'] ?? null, 190),
                'sortOrder' => isset($item['sortOrder']) ? max(0, (int) $item['sortOrder']) : count($validatedTeam),
                'isActive' => isset($item['isActive']) ? (bool) $item['isActive'] : true,
            ];
        }

        return [
            'heroKicker' => $text($request->input('heroKicker'), 120),
            'heroTitle' => $text($request->input('heroTitle'), 190),
            'heroSubtitle' => $text($request->input('heroSubtitle')),
            'missionKicker' => $text($request->input('missionKicker'), 120),
            'missionTitle' => $text($request->input('missionTitle'), 190),
            'missionLead' => $text($request->input('missionLead')),
            'missionBody1' => $text($request->input('missionBody1')),
            'missionBody2' => $text($request->input('missionBody2')),
            'missionImageUrl' => $text($request->input('missionImageUrl'), 500),
            'valuesTitle' => $text($request->input('valuesTitle'), 190),
            'valuesSubtitle' => $text($request->input('valuesSubtitle')),
            'teamTitle' => $text($request->input('teamTitle'), 190),
            'teamSubtitle' => $text($request->input('teamSubtitle')),
            'values' => $validatedValues,
            'team' => $validatedTeam,
        ];
    }

    private function assertManagerAccess(Request $request): void
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('You are not allowed to manage about page settings.', 403);
        }
    }
}
