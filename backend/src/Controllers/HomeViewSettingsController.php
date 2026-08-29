<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\HomeViewSettingsRepository;
use RuntimeException;

final class HomeViewSettingsController
{
    private const MANAGER_ROLES = ['manager', 'admin'];

    public function __construct(
        private readonly HomeViewSettingsRepository $homeViewSettings,
    ) {
    }

    public function index(Request $request): array
    {
        $settings = $this->homeViewSettings->getSettings();

        if (!$settings) {
            return [
                'settings' => null,
                'featuredItems' => [],
            ];
        }

        return [
            'settings' => $settings,
            'featuredItems' => $settings['featuredItems'] ?? [],
        ];
    }

    public function store(Request $request): array
    {
        $this->assertManagerAccess($request);
        $payload = $this->validatedPayload($request);
        $result = $this->homeViewSettings->create($payload);

        return [
            'message' => 'Home view settings created successfully.',
            'settings' => $result,
            'status' => 201,
        ];
    }

    public function update(Request $request): array
    {
        $this->assertManagerAccess($request);
        $payload = $this->validatedPayload($request);
        $result = $this->homeViewSettings->update($payload);

        if (!$result) {
            throw new RuntimeException('Home view settings not found.', 404);
        }

        return [
            'message' => 'Home view settings updated successfully.',
            'settings' => $result,
        ];
    }

    private function validatedPayload(Request $request): array
    {
        $heroBackgroundImage = trim((string) ($request->input('heroBackgroundImage') ?? ''));
        $heroKicker = trim((string) ($request->input('heroKicker') ?? ''));
        $heroTitle = trim((string) ($request->input('heroTitle') ?? ''));
        $heroCopy = trim((string) ($request->input('heroCopy') ?? ''));
        $heroPrimaryButtonText = trim((string) ($request->input('heroPrimaryButtonText') ?? ''));
        $heroPrimaryButtonLink = trim((string) ($request->input('heroPrimaryButtonLink') ?? ''));
        $heroSecondaryButtonText = trim((string) ($request->input('heroSecondaryButtonText') ?? ''));
        $heroSecondaryButtonLink = trim((string) ($request->input('heroSecondaryButtonLink') ?? ''));
        $featuredTitle = trim((string) ($request->input('featuredTitle') ?? ''));
        $featuredSubtitle = trim((string) ($request->input('featuredSubtitle') ?? ''));
        $featuredItems = $request->input('featuredItems') ?? [];

        if (!is_array($featuredItems)) {
            $featuredItems = [];
        }

        $validatedFeaturedItems = [];
        foreach ($featuredItems as $item) {
            if (!is_array($item)) continue;

            $validatedFeaturedItems[] = [
                'label' => trim((string) ($item['label'] ?? '')),
                'routeCategory' => trim((string) ($item['routeCategory'] ?? '')),
                'title' => trim((string) ($item['title'] ?? '')),
                'eyebrow' => trim((string) ($item['eyebrow'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
                'productId' => isset($item['productId']) && $item['productId'] !== '' ? (int) $item['productId'] : null,
                'sortOrder' => isset($item['sortOrder']) ? (int) $item['sortOrder'] : 0,
                'isActive' => isset($item['isActive']) ? (bool) $item['isActive'] : true,
            ];
        }

        return [
            'heroBackgroundImage' => $heroBackgroundImage !== '' ? $heroBackgroundImage : null,
            'heroKicker' => $heroKicker !== '' ? $heroKicker : null,
            'heroTitle' => $heroTitle !== '' ? $heroTitle : null,
            'heroCopy' => $heroCopy !== '' ? $heroCopy : null,
            'heroPrimaryButtonText' => $heroPrimaryButtonText !== '' ? $heroPrimaryButtonText : null,
            'heroPrimaryButtonLink' => $heroPrimaryButtonLink !== '' ? $heroPrimaryButtonLink : null,
            'heroSecondaryButtonText' => $heroSecondaryButtonText !== '' ? $heroSecondaryButtonText : null,
            'heroSecondaryButtonLink' => $heroSecondaryButtonLink !== '' ? $heroSecondaryButtonLink : null,
            'featuredTitle' => $featuredTitle !== '' ? $featuredTitle : null,
            'featuredSubtitle' => $featuredSubtitle !== '' ? $featuredSubtitle : null,
            'featuredItems' => $validatedFeaturedItems,
        ];
    }

    private function assertManagerAccess(Request $request): void
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('You are not allowed to manage home view settings.', 403);
        }
    }
}