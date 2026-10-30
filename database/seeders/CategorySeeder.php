<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'skincare',
                'sort_order' => 0,
                'name' => 'Skincare',
                'description' => 'Daily essentials for healthy, glowing skin.',
            ],
            [
                'slug' => 'makeup',
                'sort_order' => 1,
                'name' => 'Makeup',
                'description' => 'Colour and coverage for every occasion.',
            ],
            [
                'slug' => 'body-care',
                'sort_order' => 2,
                'name' => 'Body Care',
                'description' => 'Nourishing care from head to toe.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
