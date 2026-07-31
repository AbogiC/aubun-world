<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\MixMatchService;
use PDO;
use Throwable;

final class MixMatchRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly MixMatchService $mixMatch
    ) {
    }

    public function allForUser(int $userId, ?string $customerCountry = null): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, created_at, updated_at
             FROM looks
             WHERE user_id = :user_id
             ORDER BY updated_at DESC, id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        $rows = $statement->fetchAll();
        $itemsByLook = $this->itemsGroupedByLookIds(array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        ));

        $looks = [];

        foreach ($rows as $row) {
            $looks[] = $this->hydrateLook(
                $row,
                $itemsByLook[(int) $row['id']] ?? [],
                $customerCountry
            );
        }

        return $looks;
    }

    public function find(int $id, int $userId, ?string $customerCountry = null): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, created_at, updated_at
             FROM looks
             WHERE id = :id AND user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId]);

        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        $itemsByLook = $this->itemsGroupedByLookIds([(int) $row['id']]);

        return $this->hydrateLook(
            $row,
            $itemsByLook[(int) $row['id']] ?? [],
            $customerCountry
        );
    }

    public function create(int $userId, string $name, array $pieces): array
    {
        try {
            $this->pdo->beginTransaction();

            $insert = $this->pdo->prepare(
                'INSERT INTO looks (user_id, name, created_at, updated_at)
                 VALUES (:user_id, :name, NOW(), NOW())'
            );
            $insert->execute(['user_id' => $userId, 'name' => $name]);

            $lookId = (int) $this->pdo->lastInsertId();
            $this->insertItems($lookId, $pieces);

            $this->pdo->commit();

            return $this->find($lookId, $userId);
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function update(int $id, int $userId, string $name, array $pieces): ?array
    {
        if (!$this->ownsLook($id, $userId)) {
            return null;
        }

        try {
            $this->pdo->beginTransaction();

            $update = $this->pdo->prepare(
                'UPDATE looks SET name = :name, updated_at = NOW() WHERE id = :id'
            );
            $update->execute(['name' => $name, 'id' => $id]);

            $delete = $this->pdo->prepare('DELETE FROM look_items WHERE look_id = :look_id');
            $delete->execute(['look_id' => $id]);

            $this->insertItems($id, $pieces);

            $this->pdo->commit();

            return $this->find($id, $userId);
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id, int $userId): bool
    {
        if (!$this->ownsLook($id, $userId)) {
            return false;
        }

        $statement = $this->pdo->prepare('DELETE FROM looks WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function ownsLook(int $id, int $userId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT id FROM looks WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId]);

        return (bool) $statement->fetch();
    }

    private function insertItems(int $lookId, array $pieces): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO look_items (look_id, slot, product_id, size, color, created_at, updated_at)
             VALUES (:look_id, :slot, :product_id, :size, :color, NOW(), NOW())'
        );

        foreach ($pieces as $piece) {
            $statement->execute([
                'look_id' => $lookId,
                'slot' => $piece['slot'],
                'product_id' => (int) $piece['productId'],
                'size' => $piece['size'],
                'color' => $piece['color'],
            ]);
        }
    }

    private function itemsGroupedByLookIds(array $lookIds): array
    {
        if ($lookIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($lookIds), '?'));
        $statement = $this->pdo->prepare(
            "SELECT id, look_id, slot, product_id, size, color
             FROM look_items
             WHERE look_id IN ($placeholders)
             ORDER BY id ASC"
        );
        $statement->execute($lookIds);

        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $lookId = (int) $row['look_id'];
            $grouped[$lookId][] = [
                'slot' => $row['slot'],
                'productId' => (int) $row['product_id'],
                'size' => $row['size'],
                'color' => $row['color'],
            ];
        }

        return $grouped;
    }

    private function hydrateLook(array $row, array $items, ?string $customerCountry): array
    {
        $pieces = $this->mixMatch->resolveStoredLook($items, $customerCountry);
        $total = array_sum(array_column($pieces, 'price'));

        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'pieces' => $pieces,
            'total' => round((float) $total, 2),
            'count' => count($pieces),
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }
}
