<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Request;
use App\Repositories\ProductRepository;
use finfo;
use RuntimeException;

final class ProductController
{
    private const MANAGER_ROLES = ['manager', 'admin'];
    private const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const MAX_IMAGE_BYTES = 5_242_880;

    public function __construct(
        private readonly ProductRepository $products,
        private readonly string $productImageDirectory
    )
    {
    }

    public function index(Request $request): array
    {
        $country = $this->normalizedCountry($request->header('X-Customer-Country'));

        return [
            'products' => $this->products->all([
                'category' => $request->queryParam('category'),
                'featured' => $request->queryParam('featured'),
                'min_price' => $request->queryParam('min_price'),
                'max_price' => $request->queryParam('max_price'),
                'sort' => $request->queryParam('sort'),
            ], $country),
        ];
    }

    public function show(Request $request): array
    {
        $product = $this->products->find(
            (int) $request->attribute('id'),
            $this->normalizedCountry($request->header('X-Customer-Country'))
        );

        if (!$product) {
            throw new RuntimeException('Product not found.', 404);
        }

        return ['product' => $product];
    }

    public function store(Request $request): array
    {
        $this->assertManagerAccess($request);

        return [
            'message' => 'Product created successfully.',
            'product' => $this->products->create($this->validatedPayload($request)),
            'status' => 201,
        ];
    }

    public function update(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        if (!$this->products->find($id)) {
            throw new RuntimeException('Product not found.', 404);
        }

        return [
            'message' => 'Product updated successfully.',
            'product' => $this->products->update($id, $this->validatedPayload($request)),
        ];
    }

    public function destroy(Request $request): array
    {
        $this->assertManagerAccess($request);
        $id = (int) $request->attribute('id');

        if (!$this->products->delete($id)) {
            throw new RuntimeException('Product not found.', 404);
        }

        return [
            'message' => 'Product deleted successfully.',
        ];
    }

    public function uploadImage(Request $request): array
    {
        $this->assertManagerAccess($request);

        $file = $request->file('image');

        if (!$file || !isset($file['tmp_name'], $file['name'], $file['error'], $file['size'])) {
            throw new RuntimeException('Product image is required.', 422);
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed.', 422);
        }

        if ((int) $file['size'] > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('Image must be 5 MB or smaller.', 422);
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
            throw new RuntimeException('Only JPG, JPEG, PNG, WEBP, and GIF images are allowed.', 422);
        }

        $mimeType = $this->detectMimeType((string) $file['tmp_name']);

        if (!str_starts_with($mimeType, 'image/')) {
            throw new RuntimeException('Uploaded file must be an image.', 422);
        }

        if (
            !is_dir($this->productImageDirectory)
            && !mkdir($this->productImageDirectory, 0775, true)
            && !is_dir($this->productImageDirectory)
        ) {
            throw new RuntimeException('Unable to create product image directory.', 500);
        }

        $filename = sprintf('%s.%s', bin2hex(random_bytes(16)), $extension);
        $destination = $this->productImageDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new RuntimeException('Unable to store uploaded image.', 500);
        }

        return [
            'message' => 'Image uploaded successfully.',
            'image' => [
                'filename' => $filename,
                'url' => '/api/product-images/' . rawurlencode($filename),
            ],
            'status' => 201,
        ];
    }

    public function image(Request $request): never
    {
        $filename = basename((string) $request->attribute('filename'));
        $path = $this->productImageDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!is_file($path)) {
            throw new RuntimeException('Image not found.', 404);
        }

        Response::file($path, $this->detectMimeType($path));
        exit;
    }

    private function assertManagerAccess(Request $request): void
    {
        $role = (string) ($request->attribute('user')['role'] ?? '');

        if (!in_array($role, self::MANAGER_ROLES, true)) {
            throw new RuntimeException('You are not allowed to manage products.', 403);
        }
    }

    private function validatedPayload(Request $request): array
    {
        $name = trim((string) $request->input('name'));
        $category = trim((string) $request->input('category'));
        $image = trim((string) $request->input('image'));
        $description = trim((string) $request->input('description'));
        $sizes = $this->normalizedList($request->input('sizes', []));
        $colors = $this->normalizedList($request->input('colors', []));
        $price = (float) $request->input('price', 0);
        $originalPriceInput = $request->input('originalPrice');
        $originalPrice = $originalPriceInput === null || $originalPriceInput === '' ? null : (float) $originalPriceInput;
        $rating = (float) $request->input('rating', 0);
        $reviews = (int) $request->input('reviews', 0);
        $featured = (bool) $request->input('featured', false);
        $countryPrices = $this->normalizedCountryPrices($request->input('countryPrices', []));

        if ($name === '' || $category === '' || $image === '' || $description === '') {
            throw new RuntimeException('Name, category, image, and description are required.', 422);
        }

        if ($price <= 0) {
            throw new RuntimeException('Price must be greater than zero.', 422);
        }

        if ($originalPrice !== null && $originalPrice < $price) {
            throw new RuntimeException('Original price must be greater than or equal to price.', 422);
        }

        if ($sizes === []) {
            throw new RuntimeException('At least one size is required.', 422);
        }

        if ($colors === []) {
            throw new RuntimeException('At least one color is required.', 422);
        }

        if ($rating < 0 || $rating > 5) {
            throw new RuntimeException('Rating must be between 0 and 5.', 422);
        }

        if ($reviews < 0) {
            throw new RuntimeException('Reviews cannot be negative.', 422);
        }

        return [
            'name' => $name,
            'category' => $category,
            'price' => $price,
            'originalPrice' => $originalPrice,
            'image' => $image,
            'description' => $description,
            'rating' => $rating,
            'reviews' => $reviews,
            'sizes' => $sizes,
            'colors' => $colors,
            'featured' => $featured,
            'countryPrices' => $countryPrices,
        ];
    }

    private function normalizedList(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $values
        )));
    }

    private function detectMimeType(string $path): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path) ?: 'application/octet-stream';

        return (string) $mimeType;
    }

    private function normalizedCountryPrices(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $countryPrices = [];
        $seenCountries = [];

        foreach ($values as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $countryName = trim((string) ($entry['countryName'] ?? ''));
            $priceValue = $entry['price'] ?? null;

            if ($countryName === '' && ($priceValue === null || $priceValue === '')) {
                continue;
            }

            if ($countryName === '') {
                throw new RuntimeException('Country name is required for country-based pricing.', 422);
            }

            if ($priceValue === null || $priceValue === '') {
                throw new RuntimeException('Country-based price is required.', 422);
            }

            $normalizedKey = strtolower($countryName);

            if (isset($seenCountries[$normalizedKey])) {
                throw new RuntimeException('Each country can only be added once per product.', 422);
            }

            $price = (float) $priceValue;

            if ($price <= 0) {
                throw new RuntimeException('Country-based price must be greater than zero.', 422);
            }

            $countryPrices[] = [
                'countryName' => $countryName,
                'price' => $price,
            ];
            $seenCountries[$normalizedKey] = true;
        }

        return $countryPrices;
    }

    private function normalizedCountry(?string $country): ?string
    {
        $value = trim((string) $country);

        return $value !== '' ? $value : null;
    }
}
