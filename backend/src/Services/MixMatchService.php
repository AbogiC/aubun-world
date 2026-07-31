<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MixMatchConfigRepository;
use App\Repositories\ProductRepository;
use RuntimeException;

final class MixMatchService
{
    private ?array $slotsCache = null;
    private ?array $presetsCache = null;

    public function __construct(
        private readonly ProductRepository $products,
        private readonly MixMatchConfigRepository $config
    ) {
    }

    public function config(): array
    {
        return [
            'slots' => $this->slots(),
            'presets' => $this->presets(),
            'categories' => array_values(array_diff($this->products->categories(), ['All'])),
        ];
    }

    public function isValidSlot(string $key): bool
    {
        return $this->slot($key) !== null;
    }

    public function slot(string $key): ?array
    {
        foreach ($this->slots() as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return null;
    }

    public function categoriesForSlot(string $key): array
    {
        return $this->slot($key)['categories'] ?? [];
    }

    private function slots(): array
    {
        return $this->slotsCache ??= $this->config->slots();
    }

    private function presets(): array
    {
        return $this->presetsCache ??= $this->config->presets();
    }

    /**
     * Validates the raw request payload into a list of { slot, productId, size, color } pieces.
     *
     * @throws RuntimeException when the payload shape is invalid.
     */
    public function normalizePieces(mixed $pieces): array
    {
        if (!is_array($pieces) || $pieces === []) {
            throw new RuntimeException('At least one piece is required.', 422);
        }

        $normalized = [];
        $seenSlots = [];

        foreach ($pieces as $index => $piece) {
            $label = sprintf('Piece #%d', $index + 1);

            if (!is_array($piece)) {
                throw new RuntimeException(sprintf('%s is invalid.', $label), 422);
            }

            $slot = strtolower(trim((string) ($piece['slot'] ?? '')));
            $productId = filter_var($piece['productId'] ?? null, FILTER_VALIDATE_INT);
            $size = trim((string) ($piece['size'] ?? ''));
            $color = trim((string) ($piece['color'] ?? ''));

            if (!$this->isValidSlot($slot)) {
                throw new RuntimeException(sprintf('Unknown mix & match slot "%s".', $slot), 422);
            }

            if ($productId === false || $productId <= 0) {
                throw new RuntimeException(sprintf('%s has an invalid product id.', $label), 422);
            }

            if ($size === '') {
                throw new RuntimeException(sprintf('%s requires a size.', $label), 422);
            }

            if ($color === '') {
                throw new RuntimeException(sprintf('%s requires a colour.', $label), 422);
            }

            if (isset($seenSlots[$slot])) {
                throw new RuntimeException(sprintf('The "%s" slot can only be used once.', $slot), 422);
            }

            $seenSlots[$slot] = true;
            $normalized[] = [
                'slot' => $slot,
                'productId' => (int) $productId,
                'size' => $size,
                'color' => $color,
            ];
        }

        return $normalized;
    }

    /**
     * Resolves and validates a composed look strictly. Throws when any piece is
     * unavailable, no longer shown, out of slot, or uses a missing size/colour.
     *
     * @return array{pieces: array, total: float, count: int}
     */
    public function resolveLook(array $pieces, ?string $customerCountry): array
    {
        $resolved = [];

        foreach ($pieces as $piece) {
            $product = $this->products->find((int) $piece['productId'], $customerCountry);

            if (!$product || $product['isShowed'] === false) {
                throw new RuntimeException('One of the selected pieces is no longer available.', 422);
            }

            if (!in_array($product['category'], $this->categoriesForSlot($piece['slot']), true)) {
                $definition = $this->slot($piece['slot']);
                throw new RuntimeException(
                    sprintf('"%s" does not belong in the %s slot.', $product['name'], $definition['label'] ?? $piece['slot']),
                    422
                );
            }

            if (!in_array($piece['size'], $product['sizes'], true)) {
                throw new RuntimeException(sprintf('Size "%s" is not available for "%s".', $piece['size'], $product['name']), 422);
            }

            if (!in_array($piece['color'], $product['colors'], true)) {
                throw new RuntimeException(sprintf('Colour "%s" is not available for "%s".', $piece['color'], $product['name']), 422);
            }

            $resolved[] = $this->piecePayload($piece['slot'], $product, $piece['size'], $piece['color']);
        }

        return $this->lookPayload($resolved);
    }

    /**
     * Resolves stored look items leniently so that an edited or removed product
     * never breaks the whole list. Unavailable pieces are skipped and the total
     * is recomputed from what remains.
     *
     * @return array
     */
    public function resolveStoredLook(array $items, ?string $customerCountry): array
    {
        $resolved = [];

        foreach ($items as $item) {
            if (!$this->isValidSlot($item['slot'])) {
                continue;
            }

            $product = $this->products->find((int) $item['productId'], $customerCountry);

            if (!$product || $product['isShowed'] === false) {
                continue;
            }

            $size = in_array($item['size'], $product['sizes'], true)
                ? $item['size']
                : ($product['sizes'][0] ?? '');
            $color = in_array($item['color'], $product['colors'], true)
                ? $item['color']
                : ($product['colors'][0] ?? '');

            if ($size === '' || $color === '') {
                continue;
            }

            $resolved[] = $this->piecePayload($item['slot'], $product, $size, $color);
        }

        return $resolved;
    }

    private function piecePayload(string $slot, array $product, string $size, string $color): array
    {
        $definition = $this->slot($slot);

        return [
            'slot' => $slot,
            'slotLabel' => $definition['label'] ?? $slot,
            'productId' => (int) $product['id'],
            'name' => $product['name'],
            'category' => $product['category'],
            'image' => $product['image'],
            'price' => (float) $product['price'],
            'sizes' => $product['sizes'],
            'colors' => $product['colors'],
            'size' => $size,
            'color' => $color,
        ];
    }

    private function lookPayload(array $pieces): array
    {
        $total = array_sum(array_column($pieces, 'price'));

        return [
            'pieces' => $pieces,
            'total' => round((float) $total, 2),
            'count' => count($pieces),
        ];
    }
}
