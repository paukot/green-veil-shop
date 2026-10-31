<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;

class BreadcrumbService
{
    public function getCategoryBreadcrumbs(Category $category): array
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => '/'],
        ];

        if ($category->parent) {
            $breadcrumbs[] = [
                'name' => $category->parent->name,
                'url' => route('categories.show', $category->parent),
            ];
        }

        $breadcrumbs[] = [
            'name' => $category->name,
            'url' => route('categories.show', $category),
        ];

        return $breadcrumbs;
    }

    public function getProductBreadcrumbs(Product $product): array
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => 'Products', 'url' => route('products.index')],
        ];

        if ($product->category) {
            $breadcrumbs[] = [
                'name' => $product->category->name,
                'url' => route('categories.show', $product->category),
            ];
        }

        $breadcrumbs[] = [
            'name' => $product->name,
            'url' => route('products.show', $product),
        ];

        return $breadcrumbs;
    }
}
