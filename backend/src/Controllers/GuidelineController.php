<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\GuidelineRepository;
use App\Repositories\NotificationRepository;
use RuntimeException;

final class GuidelineController
{
    private const MANAGER_ROLES = ['manager', 'admin'];
    private const VALID_TYPES = ['size_guide', 'fit_guide', 'care_instruction', 'product_guide'];

    public function __construct(
        private readonly GuidelineRepository $guidelines,
        private readonly NotificationRepository $notifications,
    ) {
    }

    public function index(Request $request): array
    {
        $type = trim((string) $request->queryParam('type', ''));

        if ($type !== '') {
            if (!in_array($type, self::VALID_TYPES, true)) {
                throw new RuntimeException('Invalid guideline type.', 422);
            }
            return ['guidelines' => $this->guidelines->findByType($type)];
        }

        return ['guidelines' => $this->guidelines->all()];
    }

    public function show(Request $request): array
    {
        $id = (int) $request->attribute('id');
        $guideline = $this->guidelines->find($id);

        if (!$guideline) {
            throw new RuntimeException('Guideline not found.', 404);
        }

        return ['guideline' => $guideline];
    }

    public function store(Request $request): array
    {
        $this->assertManagerAccess($request);
        $payload = $this->validatedPayload($request);
        $guideline = $this->guidelines->create($payload);

        $this->notifications->createForAllSubscribed(
            'guideline_update',
            'New Guideline Added',
            sprintf('A new "%s" guideline is now available: %s', $guideline['type'], $guideline['title']),
            '/guidlines'
        );

        return [
            'message' => 'Guideline created successfully.',
            'guideline' => $guideline,
            'status' => 201,
        ];
    }

    public function update(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        $existing = $this->guidelines->find($id);

        if (!$existing) {
            throw new RuntimeException('Guideline not found.', 404);
        }

        $payload = $this->validatedPayload($request);
        $guideline = $this->guidelines->update($id, $payload);

        $this->notifications->createForAllSubscribed(
            'guideline_update',
            'Guideline Updated',
            sprintf('The "%s" guideline has been updated.', $guideline['title']),
            '/guidlines'
        );

        return [
            'message' => 'Guideline updated successfully.',
            'guideline' => $guideline,
        ];
    }

    public function destroy(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        if (!$this->guidelines->delete($id)) {
            throw new RuntimeException('Guideline not found.', 404);
        }

        return [
            'message' => 'Guideline deleted successfully.',
        ];
    }

    private function validatedPayload(Request $request): array
    {
        $type = trim((string) $request->input('type'));
        $title = trim((string) $request->input('title'));
        $content = $request->input('content');
        $sortOrder = (int) $request->input('sortOrder', 0);
        $isActive = (bool) $request->input('isActive', true);

        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new RuntimeException('Invalid guideline type.', 422);
        }

        if ($title === '') {
            throw new RuntimeException('Guideline title is required.', 422);
        }

        if (!is_array($content) || $content === []) {
            throw new RuntimeException('Guideline content is required and must be a JSON object.', 422);
        }

        return [
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'sortOrder' => max(0, $sortOrder),
            'isActive' => $isActive,
        ];
    }

    private function assertManagerAccess(Request $request): void
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('You are not allowed to manage guidelines.', 403);
        }
    }
}
