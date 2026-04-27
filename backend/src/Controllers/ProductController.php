<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\ProductRepository;
use RuntimeException;

final class ProductController
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function index(Request $request): array
    {
        return [
            'products' => $this->products->all([
                'category' => $request->queryParam('category'),
                'featured' => $request->queryParam('featured'),
                'min_price' => $request->queryParam('min_price'),
                'max_price' => $request->queryParam('max_price'),
                'sort' => $request->queryParam('sort'),
            ]),
        ];
    }

    public function show(Request $request): array
    {
        $product = $this->products->find((int) $request->attribute('id'));

        if (!$product) {
            throw new RuntimeException('Product not found.', 404);
        }

        return ['product' => $product];
    }
}
