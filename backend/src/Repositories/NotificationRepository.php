<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class NotificationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, type, title, message, link, is_read, created_at
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return array_map(fn (array $row): array => $this->mapNotification($row), $statement->fetchAll());
    }

    public function countUnread(int $userId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute(['id' => $notificationId, 'user_id' => $userId]);

        return $statement->rowCount() > 0;
    }

    public function markAllAsRead(int $userId): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->rowCount() > 0;
    }

    public function delete(int $notificationId, int $userId): bool
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM notifications WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute(['id' => $notificationId, 'user_id' => $userId]);

        return $statement->rowCount() > 0;
    }

    public function createForAllSubscribed(string $type, string $title, string $message, ?string $link = null): int
    {
        $subscribedUserIds = $this->getSubscribedUserIds($type);

        if ($subscribedUserIds === []) {
            return 0;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, type, title, message, link, created_at)
             VALUES (:user_id, :type, :title, :message, :link, NOW())'
        );

        $count = 0;
        foreach ($subscribedUserIds as $userId) {
            $insert->execute([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
            ]);
            $count++;
        }

        return $count;
    }

    public function getSubscriptions(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT type, subscribed FROM notification_subscriptions WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        $rows = $statement->fetchAll();
        $types = ['new_collection' => true, 'new_article' => true, 'guideline_update' => true];

        foreach ($rows as $row) {
            $types[$row['type']] = (bool) $row['subscribed'];
        }

        return $types;
    }

    public function updateSubscriptions(int $userId, array $types): array
    {
        $validTypes = ['new_collection', 'new_article', 'guideline_update'];

        $upsert = $this->pdo->prepare(
            'INSERT INTO notification_subscriptions (user_id, type, subscribed, created_at, updated_at)
             VALUES (:user_id, :type, :subscribed, NOW(), NOW())
             ON DUPLICATE KEY UPDATE subscribed = :subscribed2, updated_at = NOW()'
        );

        foreach ($types as $type => $subscribed) {
            if (!in_array($type, $validTypes, true)) {
                continue;
            }
            $value = $subscribed ? 1 : 0;
            $upsert->execute([
                'user_id' => $userId,
                'type' => $type,
                'subscribed' => $value,
                'subscribed2' => $value,
            ]);
        }

        return $this->getSubscriptions($userId);
    }

    private function getSubscribedUserIds(string $type): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ns.user_id
             FROM notification_subscriptions ns
             WHERE ns.type = :type AND ns.subscribed = 1
             UNION
             SELECT u.id
             FROM users u
             WHERE u.id NOT IN (SELECT user_id FROM notification_subscriptions WHERE type = :type2)'
        );
        $statement->execute(['type' => $type, 'type2' => $type]);

        return array_map(fn (array $row): int => (int) $row['user_id'], $statement->fetchAll());
    }

    private function mapNotification(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'userId' => (int) $row['user_id'],
            'type' => $row['type'],
            'title' => $row['title'],
            'message' => $row['message'],
            'link' => $row['link'],
            'isRead' => (bool) $row['is_read'],
            'createdAt' => $row['created_at'],
        ];
    }
}
