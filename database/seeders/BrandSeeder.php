<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Curated authentic cosmetic brands representing major beauty houses.
     *
     * @var list<array{name: string, country: string, website: string, desc: string}>
     */
    public const CURATED_BRANDS = [
        [
            'name' => 'Maybelline New York',
            'country' => 'US',
            'website' => 'https://www.maybelline.com',
            'desc' => 'Iconic New York color cosmetics brand bringing runway trends to everyday beauty routines.',
        ],
        [
            'name' => 'L\'Oréal Paris',
            'country' => 'FR',
            'website' => 'https://www.lorealparisusa.com',
            'desc' => 'World-leading beauty house pioneering scientific innovation and accessible luxury.',
        ],
        [
            'name' => 'CeraVe',
            'country' => 'US',
            'website' => 'https://www.cerave.com',
            'desc' => 'Dermatologist-developed skincare formulated with 3 essential ceramides to restore the skin barrier.',
        ],
        [
            'name' => 'La Roche-Posay',
            'country' => 'FR',
            'website' => 'https://www.laroche-posay.com',
            'desc' => 'Thermal spring water dermocosmetics recommended by 90,000+ dermatologists worldwide.',
        ],
        [
            'name' => 'The Ordinary',
            'country' => 'CA',
            'website' => 'https://theordinary.com',
            'desc' => 'Clinical formulations with integrity, offering targeted active ingredients at transparent prices.',
        ],
        [
            'name' => 'Green Veil Organics',
            'country' => 'PL',
            'website' => 'https://greenveil.shop',
            'desc' => 'Clean botanical skincare handcrafted in Poland with certified organic plant extracts.',
        ],
        [
            'name' => 'COSRX',
            'country' => 'KR',
            'website' => 'https://www.cosrx.com',
            'desc' => 'Korean skincare leader renowned for gentle BHA, AHA, and snail mucin barrier treatments.',
        ],
        [
            'name' => 'Rituals',
            'country' => 'NL',
            'website' => 'https://www.rituals.com',
            'desc' => 'Luxurious body care and home fragrance inspired by ancient Eastern wellness traditions.',
        ],
        [
            'name' => 'Bioderma',
            'country' => 'FR',
            'website' => 'https://www.bioderma.com',
            'desc' => 'Biomimetic dermatological skincare and inventors of Sensibio H2O micellar technology.',
        ],
        [
            'name' => 'Kiehl\'s',
            'country' => 'US',
            'website' => 'https://www.kiehls.com',
            'desc' => 'Historic New York apothecary heritage crafting efficacious skincare formulas since 1851.',
        ],
        [
            'name' => 'NYX Professional Makeup',
            'country' => 'US',
            'website' => 'https://www.nyxcosmetics.com',
            'desc' => 'Pro-level pigments, inclusive shade ranges, and cruelty-free makeup essentials.',
        ],
        [
            'name' => 'Vichy Laboratories',
            'country' => 'FR',
            'website' => 'https://www.vichyusa.com',
            'desc' => 'Volcanic mineralizing water skincare formulated to strengthen and defend the skin barrier.',
        ],
    ];

    public function run(): void
    {
        $now = Carbon::now();

        foreach (self::CURATED_BRANDS as $index => $brandData) {
            DB::table('brands')->updateOrInsert(
                ['slug' => Str::slug($brandData['name'])],
                [
                    'name' => $brandData['name'],
                    'country_of_origin' => $brandData['country'],
                    'website_url' => $brandData['website'],
                    'description' => $brandData['desc'],
                    'logo_path' => 'brands/'.Str::slug($brandData['name']).'.png',
                    'is_active' => true,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
