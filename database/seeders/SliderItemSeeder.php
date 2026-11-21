<?php

namespace Database\Seeders;

use App\Models\SliderItem;
use Database\Seeders\Support\SeedImages;
use Illuminate\Database\Seeder;

class SliderItemSeeder extends Seeder
{
    public function run(): void
    {
        $seedImages = SeedImages::make();

        $slides = [
            [
                'title' => 'Botanical care, simply made',
                'subtitle' => 'Thoughtful formulas for your daily ritual.',
                'image' => $seedImages->slideImage('skincare'),
                'url' => '/products',
                'sort_order' => 0,
                'is_active' => true,
                'alt_text' => 'Green Veil botanical skincare collection',
                'button_label' => 'Shop collection',
            ],
            [
                'title' => 'A slower approach to beauty',
                'subtitle' => 'Small rituals, considered ingredients, visible results.',
                'image' => $seedImages->slideImage('favourites'),
                'url' => '/products',
                'sort_order' => 1,
                'is_active' => true,
                'alt_text' => 'Green Veil featured products',
                'button_label' => 'Explore favourites',
            ],
            [
                'title' => 'Artisanal Perfumes & Botanical Scents',
                'subtitle' => 'Rare botanicals, distilled into timeless essences.',
                'image' => $seedImages->slideImage('perfumes'),
                'url' => '/categories/perfumes',
                'sort_order' => 2,
                'is_active' => true,
                'alt_text' => 'Green Veil luxury fragrances',
                'button_label' => 'Discover scents',
            ],
            [
                'title' => 'Luminous Clean Makeup',
                'subtitle' => 'Breathable mineral formulas that enhance your natural glow.',
                'image' => $seedImages->slideImage('makeup'),
                'url' => '/categories/makeup',
                'sort_order' => 3,
                'is_active' => true,
                'alt_text' => 'Green Veil clean cosmetics',
                'button_label' => 'View makeup',
            ],
        ];

        // Drop slides left over from older seeds that pointed at missing files or dead external links.
        SliderItem::query()
            ->where(fn ($query) => $query->where('image', 'like', 'sliders/%')->orWhere('image', 'like', 'http%'))
            ->delete();

        foreach ($slides as $slide) {
            if ($slide['image'] === null) {
                $this->command?->warn("Skipped slide '{$slide['title']}': its image was not downloaded (run app:fetch-seed-images).");

                continue;
            }

            SliderItem::updateOrCreate(['title' => $slide['title']], $slide);
        }
    }
}
