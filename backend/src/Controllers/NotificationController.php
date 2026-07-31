<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\NotificationRepository;
use RuntimeException;

final class NotificationController
{
    public function __construct(
        private readonly NotificationRepository $notifications
    ) {
    }

    public function index(Request $request): array
    {
        $user = $this->authenticatedUser($request);
        $limit = min((int) $request->queryParam('limit', 50), 100);
        $offset = max((int) $request->queryParam('offset', 0), 0);

        return [
            'notifications' => $this->notifications->findByUser((int) $user['id'], $limit, $offset),
            'unreadCount' => $this->notifications->countUnread((int) $user['id']),
        ];
    }

    public function unreadCount(Request $request): array
    {
        $user = $this->authenticatedUser($request);

        return [
            'unreadCount' => $this->notifications->countUnread((int) $user['id']),
        ];
    }

    public function markRead(Request $request): array
    {
        $user = $this->authenticatedUser($request);
        $id = (int) $request->attribute('id');

        if (!$this->notifications->markAsRead($id, (int) $user['id'])) {
            throw new RuntimeException('Notification not found.', 404);
        }

        return ['message' => 'Notification marked as read.'];
    }

    public function markAllRead(Request $request): array
    {
        $user = $this->authenticatedUser($request);
        $this->notifications->markAllAsRead((int) $user['id']);

        return ['message' => 'All notifications marked as read.'];
    }

    public function destroy(Request $request): array
    {
        $user = $this->authenticatedUser($request);
        $id = (int) $request->attribute('id');

        if (!$this->notifications->delete($id, (int) $user['id'])) {
            throw new RuntimeException('Notification not found.', 404);
        }

        return ['message' => 'Notification deleted.'];
    }

    public function getSubscriptions(Request $request): array
    {
        $user = $this->authenticatedUser($request);

        return [
            'subscriptions' => $this->notifications->getSubscriptions((int) $user['id']),
        ];
    }

    public function updateSubscriptions(Request $request): array
    {
        $user = $this->authenticatedUser($request);
        $types = $request->input('subscriptions');

        if (!is_array($types)) {
            throw new RuntimeException('Invalid subscriptions payload.', 422);
        }

        return [
            'message' => 'Notification preferences updated.',
            'subscriptions' => $this->notifications->updateSubscriptions((int) $user['id'], $types),
        ];
    }

    private function authenticatedUser(Request $request): array
    {
        $user = $request->attribute('user');

        if (!$user || !isset($user['id'])) {
            throw new RuntimeException('Authentication required.', 401);
        }

        return $user;
    }
}
