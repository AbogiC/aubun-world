<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\HomeViewSettingsRepository;
use RuntimeException;

final class HomeViewSettingsController
{
    private const MANAGER_ROLES = ['manager', 'admin'];
    private const MAX_FONT_BYTES = 10_485_760;

    public function __construct(
        private readonly HomeViewSettingsRepository $homeViewSettings,
        private readonly string $fontDirectory = '',
        private readonly string $baseUrl = '',
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

    public function uploadFont(Request $request): array
    {
        $this->assertManagerAccess($request);

        $file = $request->file('font');

        if (!$file || !isset($file['tmp_name'], $file['name'], $file['error'], $file['size'])) {
            throw new RuntimeException('Font file (.ttf) is required.', 422);
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Font upload failed.', 422);
        }

        if ((int) $file['size'] <= 0) {
            throw new RuntimeException('Uploaded font file is empty.', 422);
        }

        if ((int) $file['size'] > self::MAX_FONT_BYTES) {
            throw new RuntimeException('Font must be 10 MB or smaller.', 422);
        }

        $originalName = (string) $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension !== 'ttf') {
            throw new RuntimeException('Only .ttf font files are allowed.', 422);
        }

        if (!$this->looksLikeTrueType((string) $file['tmp_name'])) {
            throw new RuntimeException('Uploaded file is not a valid .ttf font.', 422);
        }

        $directory = $this->fontDirectory !== '' ? $this->fontDirectory : dirname(__DIR__, 3) . '/store/fonts';

        if (
            !is_dir($directory)
            && !mkdir($directory, 0775, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException('Unable to create font storage directory.', 500);
        }

        $filename = sprintf('%s.ttf', bin2hex(random_bytes(16)));
        $destination = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        // move_uploaded_file fails in some test contexts; fall back to copy.
        $stored = move_uploaded_file((string) $file['tmp_name'], $destination);
        if (!$stored) {
            $stored = copy((string) $file['tmp_name'], $destination);
        }

        if (!$stored) {
            throw new RuntimeException('Unable to store uploaded font.', 500);
        }

        $family = $this->fontFamilyFromFilename($originalName);
        $base = rtrim($this->baseUrl, '/');

        return [
            'message' => 'Font uploaded successfully. Save settings to apply it to the storefront.',
            'font' => [
                'family' => $family,
                'filename' => $filename,
                'url' => ($base !== '' ? $base : '') . '/api/fonts/' . rawurlencode($filename),
                'originalName' => $originalName,
            ],
            'status' => 201,
        ];
    }

    public function serveFont(Request $request): never
    {
        $filename = basename((string) $request->attribute('filename'));
        $directory = $this->fontDirectory !== '' ? $this->fontDirectory : dirname(__DIR__, 3) . '/store/fonts';
        $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if ($filename === '' || !preg_match('/^[a-f0-9]{32}\.ttf$/', $filename) || !is_file($path)) {
            throw new RuntimeException('Font not found.', 404);
        }

        Response::file($path, 'font/ttf');
        exit;
    }

    public function destroyFont(Request $request): array
    {
        $this->assertManagerAccess($request);

        $settings = $this->homeViewSettings->getSettings();
        $filename = $settings['customFontFilename'] ?? null;

        // Allow explicit filename as fallback (e.g. uploaded but not yet saved).
        $explicit = trim((string) ($request->input('filename') ?? ''));
        if ($explicit !== '') {
            $filename = basename($explicit);
        }

        if (is_string($filename) && $filename !== '' && preg_match('/^[a-f0-9]{32}\.ttf$/', $filename)) {
            $directory = $this->fontDirectory !== '' ? $this->fontDirectory : dirname(__DIR__, 3) . '/store/fonts';
            $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        // Clear persisted custom font so frontend falls back to default fonts.
        if ($settings) {
            $this->homeViewSettings->update([
                'heroBackgroundImage' => $settings['heroBackgroundImage'] ?? null,
                'heroKicker' => $settings['heroKicker'] ?? null,
                'heroTitle' => $settings['heroTitle'] ?? null,
                'heroCopy' => $settings['heroCopy'] ?? null,
                'heroPrimaryButtonText' => $settings['heroPrimaryButtonText'] ?? null,
                'heroPrimaryButtonLink' => $settings['heroPrimaryButtonLink'] ?? null,
                'heroSecondaryButtonText' => $settings['heroSecondaryButtonText'] ?? null,
                'heroSecondaryButtonLink' => $settings['heroSecondaryButtonLink'] ?? null,
                'featuredTitle' => $settings['featuredTitle'] ?? null,
                'featuredSubtitle' => $settings['featuredSubtitle'] ?? null,
                'customFontFamily' => null,
                'customFontUrl' => null,
                'customFontFilename' => null,
                'featuredItems' => $settings['featuredItems'] ?? [],
            ]);
        }

        return [
            'message' => 'Custom font removed. Storefront now uses default fonts.',
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
        $customFontFamily = trim((string) ($request->input('customFontFamily') ?? ''));
        $customFontUrl = trim((string) ($request->input('customFontUrl') ?? ''));
        $customFontFilename = basename(trim((string) ($request->input('customFontFilename') ?? '')));
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
            'customFontFamily' => $customFontFamily !== '' ? mb_substr($customFontFamily, 0, 120) : null,
            'customFontUrl' => $customFontUrl !== '' ? mb_substr($customFontUrl, 0, 500) : null,
            'customFontFilename' => $customFontFilename !== '' && $customFontFilename !== '.' ? mb_substr($customFontFilename, 0, 255) : null,
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

    private function looksLikeTrueType(string $tmpPath): bool
    {
        if (!is_file($tmpPath)) {
            return false;
        }

        $handle = @fopen($tmpPath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 12);
        fclose($handle);

        if (!is_string($header) || strlen($header) < 4) {
            return false;
        }

        // TrueType signatures: 0x00010000, 'true', 'typ1', 'ttcf' (collection).
        if ($header[0] === "\x00" && $header[1] === "\x01" && $header[2] === "\x00" && $header[3] === "\x00") {
            return true;
        }

        $tag = substr($header, 0, 4);

        return in_array($tag, ['true', 'typ1', 'ttcf', 'OTTO'], true);
    }

    private function fontFamilyFromFilename(string $originalName): string
    {
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $base = trim(preg_replace('/[^A-Za-z0-9 _-]+/', '', $base) ?? '');

        if ($base === '') {
            $base = 'Aubun Custom Font';
        }

        // Keep it CSS-safe and reasonably short.
        $base = preg_replace('/\s+/', ' ', $base) ?? $base;

        return mb_substr($base, 0, 120);
    }
}