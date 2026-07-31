<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\NewsRepository;
use App\Repositories\NotificationRepository;
use RuntimeException;

final class NewsController
{
    private const MANAGER_ROLES = ['manager', 'admin'];
    private const VALID_CATEGORIES = ['Collection', 'Behind the Scenes', 'Sustainability', 'Press', 'Events'];

    public function __construct(
        private readonly NewsRepository $news,
        private readonly NotificationRepository $notifications,
    ) {
    }

    public function index(Request $request): array
    {
        $category = trim((string) $request->queryParam('category', ''));
        $region = trim((string) $request->queryParam('region', ''));

        $articles = $this->news->all($region);

        if ($category !== '' && $category !== 'All') {
            $articles = array_values(array_filter(
                $articles,
                fn (array $article): bool => $article['category'] === $category
            ));
        }

        return ['articles' => $articles];
    }

    public function show(Request $request): array
    {
        $id = (int) $request->attribute('id');
        $article = $this->news->find($id);

        if (!$article) {
            throw new RuntimeException('Article not found.', 404);
        }

        return ['article' => $article];
    }

    public function store(Request $request): array
    {
        $this->assertManagerAccess($request);
        $payload = $this->validatedPayload($request);
        $article = $this->news->create($payload);

        if ($article['isPublished']) {
            $this->notifications->createForAllSubscribed(
                'new_article',
                'New Article Published',
                sprintf('Read "%s" — our latest article in %s.', $article['title'], $article['category']),
                '/news'
            );
        }

        return [
            'message' => 'Article created successfully.',
            'article' => $article,
            'status' => 201,
        ];
    }

    public function update(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        if (!$this->news->find($id)) {
            throw new RuntimeException('Article not found.', 404);
        }

        $payload = $this->validatedPayload($request);

        return [
            'message' => 'Article updated successfully.',
            'article' => $this->news->update($id, $payload),
        ];
    }

    public function destroy(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        if (!$this->news->delete($id)) {
            throw new RuntimeException('Article not found.', 404);
        }

        return [
            'message' => 'Article deleted successfully.',
        ];
    }

    private function validatedPayload(Request $request): array
    {
        $category = trim((string) $request->input('category'));
        $title = trim((string) $request->input('title'));
        $excerpt = trim((string) $request->input('excerpt'));
        $content = $request->input('content');
        $author = trim((string) $request->input('author'));
        $readTime = trim((string) ($request->input('readTime') ?? '5 min read'));
        $gradient = trim((string) ($request->input('gradient') ?? 'linear-gradient(135deg, #4d1018, #c48d0c)'));
        $icon = trim((string) ($request->input('icon') ?? 'bi bi-newspaper'));
        $region = trim((string) ($request->input('region') ?? ''));
        $isPublished = (bool) $request->input('isPublished', true);

        if (!in_array($category, self::VALID_CATEGORIES, true)) {
            throw new RuntimeException('Invalid category.', 422);
        }

        if ($title === '') {
            throw new RuntimeException('Article title is required.', 422);
        }

        if ($excerpt === '') {
            throw new RuntimeException('Article excerpt is required.', 422);
        }

        if ($author === '') {
            throw new RuntimeException('Author name is required.', 422);
        }

        return [
            'category' => $category,
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $content,
            'author' => $author,
            'readTime' => $readTime,
            'gradient' => $gradient,
            'icon' => $icon,
            'region' => $region,
            'isPublished' => $isPublished,
        ];
    }

    private function assertManagerAccess(Request $request): void
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('You are not allowed to manage news.', 403);
        }
    }
}
