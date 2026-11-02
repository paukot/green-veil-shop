<?php

namespace Database\Seeders;

use App\Models\SliderItem;
use Illuminate\Database\Seeder;

class SliderItemSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            [
                'title' => 'Botanical care, simply made',
                'subtitle' => 'Thoughtful formulas for your daily ritual.',
                'image' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=1920&q=80',
                'url' => '/shop',
                'sort_order' => 0,
                'is_active' => true,
                'alt_text' => 'Green Veil botanical skincare collection',
                'button_label' => 'Shop collection',
            ],
            [
                'title' => 'A slower approach to beauty',
                'subtitle' => 'Small rituals, considered ingredients, visible results.',
                'image' => 'https://images.unsplash.com/photo-1608248597359-0a6723c34898?auto=format&fit=crop&w=1920&q=80',
                'url' => '/shop?featured=1',
                'sort_order' => 1,
                'is_active' => true,
                'alt_text' => 'Green Veil featured products',
                'button_label' => 'Explore favourites',
            ],
            [
                'title' => 'Artisanal Perfumes & Botanical Scents',
                'subtitle' => 'Rare botanicals, distilled into timeless essences.',
                'image' => 'https://images.unsplash.com/photo-1616949755610-8c9bbc08f138?auto=format&fit=crop&w=1920&q=80',
                'url' => '/categories/perfumes',
                'sort_order' => 2,
                'is_active' => true,
                'alt_text' => 'Green Veil luxury fragrances',
                'button_label' => 'Discover scents',
            ],
            [
                'title' => 'Luminous Clean Makeup',
                'subtitle' => 'Breathable mineral formulas that enhance your natural glow.',
                'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=1920&q=80',
                'url' => '/categories/makeup',
                'sort_order' => 3,
                'is_active' => true,
                'alt_text' => 'Green Veil clean cosmetics',
                'button_label' => 'View makeup',
            ],
        ];

        // Clean up legacy non-existent relative image paths
        SliderItem::query()->where('image', 'like', 'sliders/%')->delete();

        foreach ($slides as $slide) {
            SliderItem::updateOrCreate(['title' => $slide['title']], $slide);
        }
    }
}
