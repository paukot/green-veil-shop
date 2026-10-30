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
                'image' => 'sliders/spring-botanicals.jpg',
                'url' => '/shop',
                'sort_order' => 0,
                'title' => 'Botanical care, simply made',
                'subtitle' => 'Thoughtful formulas for your daily ritual.',
                'alt_text' => 'Green Veil botanical skincare collection',
                'button_label' => 'Shop collection',
            ],
            [
                'image' => 'sliders/slow-beauty.jpg',
                'url' => '/shop?featured=1',
                'sort_order' => 1,
                'title' => 'A slower approach to beauty',
                'subtitle' => 'Small rituals, considered ingredients, visible results.',
                'alt_text' => 'Green Veil featured products',
                'button_label' => 'Explore favourites',
            ],
        ];

        foreach ($slides as $slide) {
            SliderItem::updateOrCreate(['image' => $slide['image']], $slide);
        }
    }
}
