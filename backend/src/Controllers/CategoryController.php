<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\ProductRepository;

final class CategoryController
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function index(Request $request): array
    {
        return [
            'categories' => $this->products->categories(),
        ];
    }
}
