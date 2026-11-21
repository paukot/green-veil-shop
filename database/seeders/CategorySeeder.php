<?php

namespace Database\Seeders;

use App\Models\Category;
use Database\Seeders\Support\SeedImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Seed comprehensive beauty & cosmetic category tree modeled after makeup.pl (in English).
     */
    public function run(): void
    {
        $seedImages = SeedImages::make();

        $departments = [
            [
                'slug' => 'perfumes',
                'name' => 'Perfumes',
                'description' => 'Luxury fragrances, eau de parfum, eau de toilette, and artisanal scents.',
                'sort_order' => 1,
                'children' => [
                    ['slug' => 'womens-perfumes', 'name' => "Women's Perfumes", 'description' => 'Floral, oriental, and fresh fragrances designed for women.'],
                    ['slug' => 'mens-fragrances', 'name' => "Men's Fragrances", 'description' => 'Woody, aromatic, and spicy colognes for men.'],
                    ['slug' => 'unisex-perfumes', 'name' => 'Unisex Fragrances', 'description' => 'Modern gender-neutral signature blends.'],
                    ['slug' => 'body-mists', 'name' => 'Body Mists & Sprays', 'description' => 'Light, refreshing scents for all-day wear.'],
                    ['slug' => 'niche-perfumes', 'name' => 'Niche & Artisanal Scents', 'description' => 'Rare ingredients and bespoke perfume extracts.'],
                    ['slug' => 'solid-perfumes', 'name' => 'Solid & Pocket Perfumes', 'description' => 'Compact wax-based travel perfumes.'],
                ],
            ],
            [
                'slug' => 'makeup',
                'name' => 'Makeup',
                'description' => 'Cosmetics for eyes, lips, face, brows, and nails.',
                'sort_order' => 2,
                'children' => [
                    // Eyes
                    ['slug' => 'mascaras', 'name' => 'Mascaras', 'description' => 'Volumizing, lengthening, and waterproof mascaras.'],
                    ['slug' => 'mascara-primers', 'name' => 'Mascara Primers', 'description' => 'Lash-boosting base coats for enhanced volume.'],
                    ['slug' => 'eyeshadow-primers', 'name' => 'Eyeshadow Primers', 'description' => 'Crease-free eyeshadow bases for vibrant color.'],
                    ['slug' => 'eyeshadows', 'name' => 'Eyeshadows', 'description' => 'Velvet single shadows, duochromes, and creams.'],
                    ['slug' => 'eyeshadow-palettes', 'name' => 'Eyeshadow Palettes', 'description' => 'Multi-shade palettes for daytime and evening looks.'],
                    ['slug' => 'eye-pencils', 'name' => 'Eye Pencils & Kohls', 'description' => 'Soft blendable pencils and waterline liners.'],
                    ['slug' => 'eyeliners', 'name' => 'Liquid & Gel Eyeliners', 'description' => 'Precision felt-tip and waterproof gel liners.'],
                    ['slug' => 'under-eye-concealers', 'name' => 'Under-Eye Concealers', 'description' => 'Illuminating correctors for dark circles.'],
                    ['slug' => 'lash-brow-serums', 'name' => 'Lash & Brow Serums', 'description' => 'Peptide and biotin conditioning treatments.'],
                    ['slug' => 'false-lashes', 'name' => 'False Lashes', 'description' => 'Strip lashes, individual clusters, and magnetic lashes.'],
                    ['slug' => 'lash-glue', 'name' => 'Lash Adhesives', 'description' => 'Long-lasting, gentle latex-free lash adhesives.'],
                    // Lips
                    ['slug' => 'lipsticks', 'name' => 'Lipsticks', 'description' => 'Matte, satin, and sheer botanical lipsticks.'],
                    ['slug' => 'lip-glosses', 'name' => 'Lip Glosses', 'description' => 'High-shine, non-sticky plumping glosses.'],
                    ['slug' => 'lip-liners', 'name' => 'Lip Liners', 'description' => 'Long-wearing contour pencils to define lip shape.'],
                    ['slug' => 'lip-balms', 'name' => 'Hydrating Lip Balms', 'description' => 'Deeply conditioning balms with shea and jojoba.'],
                    ['slug' => 'lip-oils', 'name' => 'Nourishing Lip Oils', 'description' => 'Glossy botanical oils packed with antioxidants.'],
                    ['slug' => 'lip-scrubs', 'name' => 'Lip Scrubs & Masks', 'description' => 'Sugar polishes and overnight lip sleeping masks.'],
                    // Brows
                    ['slug' => 'brow-pencils', 'name' => 'Eyebrow Pencils', 'description' => 'Micro-fine pencils for hair-like strokes.'],
                    ['slug' => 'brow-gels', 'name' => 'Brow Defining Gels', 'description' => 'Clear and tinted setting gels with laminating hold.'],
                    ['slug' => 'brow-pomades', 'name' => 'Brow Pomades', 'description' => 'Sculpting waterproof pomades for bold definition.'],
                    ['slug' => 'brow-powders', 'name' => 'Brow Powders', 'description' => 'Natural dual-shade compacts for soft shaping.'],
                    // Face
                    ['slug' => 'foundations', 'name' => 'Foundations', 'description' => 'Liquid, powder, and serum foundations for all skin types.'],
                    ['slug' => 'concealers', 'name' => 'Concealers & Correctors', 'description' => 'Blemish coverage and color-correcting formulas.'],
                    ['slug' => 'bb-cc-creams', 'name' => 'BB & CC Creams', 'description' => 'Lightweight tinted moisturizers with skincare benefits.'],
                    ['slug' => 'face-powders', 'name' => 'Face Powders', 'description' => 'Translucent setting and blurring finishing powders.'],
                    ['slug' => 'blushes', 'name' => 'Blushes', 'description' => 'Silky powder, liquid, and cream blush formulas.'],
                    ['slug' => 'bronzers', 'name' => 'Bronzers & Contours', 'description' => 'Sun-kissed warmth and sculpted dimensional definition.'],
                    ['slug' => 'highlighters', 'name' => 'Highlighters & Illuminators', 'description' => 'Radiant glow powders, liquids, and drop highlighters.'],
                    ['slug' => 'face-primers', 'name' => 'Face Primers', 'description' => 'Mattifying, pore-blurring, and hydrating canvas bases.'],
                    ['slug' => 'setting-sprays', 'name' => 'Setting Sprays & Mists', 'description' => 'Long-wear locking mists with dewy or matte finishes.'],
                    // Makeup Removal
                    ['slug' => 'micellar-waters', 'name' => 'Micellar Waters', 'description' => 'Rinse-free cleansing waters for face and eyes.'],
                    ['slug' => 'cleansing-balms', 'name' => 'Cleansing Balms', 'description' => 'Melting balms that dissolve waterproof cosmetics.'],
                    ['slug' => 'cleansing-oils', 'name' => 'Cleansing Oils', 'description' => 'Gentle emulsifying oils for thorough double-cleansing.'],
                    ['slug' => 'bi-phase-removers', 'name' => 'Bi-Phase Eye Makeup Removers', 'description' => 'Dual-action oil-water removers for stubborn mascara.'],
                    ['slug' => 'makeup-wipes', 'name' => 'Makeup Wipes', 'description' => 'Biodegradable soothing travel cleansing wipes.'],
                    // Nails
                    ['slug' => 'nail-polishes', 'name' => 'Classic Nail Polishes', 'description' => 'Long-wearing chip-resistant glossy nail lacquers.'],
                    ['slug' => 'gel-polishes', 'name' => 'UV/LED Gel Polishes', 'description' => 'Salon-grade gel colors cured under UV/LED lamps.'],
                    ['slug' => 'base-top-coats', 'name' => 'Base & Top Coats', 'description' => 'Protective undercoats and gel-shine top coats.'],
                    ['slug' => 'cuticle-care', 'name' => 'Cuticle Care Oils', 'description' => 'Nourishing botanical oils with almond and vitamin E.'],
                    ['slug' => 'nail-treatments', 'name' => 'Nail Treatments & Hardeners', 'description' => 'Strengthening keratin and calcium formulas.'],
                    ['slug' => 'nail-polish-removers', 'name' => 'Nail Polish Removers', 'description' => 'Acetone and acetone-free gentle formulas.'],
                    // Palettes & Tools
                    ['slug' => 'makeup-palettes', 'name' => 'Multi-Use Palettes', 'description' => 'Comprehensive palettes for eyes, cheeks, and glow.'],
                    ['slug' => 'makeup-brushes', 'name' => 'Makeup Brush Sets', 'description' => 'Vegan synthetic brushes for flawless application.'],
                    ['slug' => 'makeup-sponges', 'name' => 'Beauty Sponges & Blenders', 'description' => 'Porous blending sponges for streak-free complexion.'],
                ],
            ],
            [
                'slug' => 'hair',
                'name' => 'Hair Care',
                'description' => 'Nourishing shampoos, conditioners, masks, and styling formulas.',
                'sort_order' => 3,
                'children' => [
                    ['slug' => 'shampoos', 'name' => 'Shampoos', 'description' => 'Sulfate-free cleansing formulas for volume, moisture, and repair.'],
                    ['slug' => 'conditioners', 'name' => 'Conditioners', 'description' => 'Smoothing detanglers and hydrating rinse-out conditioners.'],
                    ['slug' => 'hair-masks', 'name' => 'Deep Repair Hair Masks', 'description' => 'Intensive weekly butter and protein treatments.'],
                    ['slug' => 'hair-oils-serums', 'name' => 'Hair Oils & Serums', 'description' => 'Frizz-taming argan, marula, and bonding oils.'],
                    ['slug' => 'leave-in-treatments', 'name' => 'Leave-In Treatments', 'description' => 'Weightless leave-in conditioners and heat protectants.'],
                    ['slug' => 'scalp-care', 'name' => 'Scalp Scrubs & Serums', 'description' => 'Exfoliating scalp treatments and caffeine growth elixirs.'],
                    ['slug' => 'hair-styling', 'name' => 'Styling & Finish', 'description' => 'Sea salt sprays, texturizing pomades, and flexible hairsprays.'],
                ],
            ],
            [
                'slug' => 'skincare',
                'name' => 'Skincare',
                'description' => 'Targeted cleansers, toners, serums, and barrier moisturizers.',
                'sort_order' => 4,
                'children' => [
                    ['slug' => 'cleansers', 'name' => 'Cleansers & Washes', 'description' => 'Gentle foaming, gel, and cream daily facial cleansers.'],
                    ['slug' => 'toners-mists', 'name' => 'Toners & Balancing Mists', 'description' => 'Hydrating rosewater and AHA/BHA clarifying toners.'],
                    ['slug' => 'face-serums', 'name' => 'Active Face Serums', 'description' => 'Concentrated Vitamin C, Retinol, Hyaluronic, and Niacinamide.'],
                    ['slug' => 'daily-moisturizers', 'name' => 'Day Moisturizers', 'description' => 'Lightweight lotions and barrier creams for daytime wear.'],
                    ['slug' => 'night-creams', 'name' => 'Night Creams & Balms', 'description' => 'Rich replenishing creams and sleeping packs.'],
                    ['slug' => 'eye-creams', 'name' => 'Eye Creams & Gels', 'description' => 'Caffeine and peptide formulas for puffiness and fine lines.'],
                    ['slug' => 'sunscreen-spf', 'name' => 'Sunscreen (SPF 30 & 50+)', 'description' => 'Mineral and invisible chemical UV protective daily creams.'],
                    ['slug' => 'face-masks', 'name' => 'Face Masks & Exfoliators', 'description' => 'Purifying clay masks, sheet masks, and chemical peels.'],
                ],
            ],
            [
                'slug' => 'body-care',
                'name' => 'Body',
                'description' => 'Aromatherapeutic shower gels, body butters, and scrubs.',
                'sort_order' => 5,
                'children' => [
                    ['slug' => 'shower-gels', 'name' => 'Shower Gels & Body Washes', 'description' => 'Botanical body washes with essential oil scents.'],
                    ['slug' => 'body-lotions', 'name' => 'Body Lotions & Milks', 'description' => 'Fast-absorbing daily hydration with aloe and glycerin.'],
                    ['slug' => 'body-butters', 'name' => 'Rich Body Butters', 'description' => 'Whipped shea, cocoa, and mango butters for dry skin.'],
                    ['slug' => 'body-scrubs', 'name' => 'Body Scrubs & Polishes', 'description' => 'Exfoliating dead sea salt and brown sugar scrubs.'],
                    ['slug' => 'bath-salts', 'name' => 'Bath Salts & Bombs', 'description' => 'Mineral soak crystals and relaxing essential oil fizzers.'],
                    ['slug' => 'hand-foot-care', 'name' => 'Hand & Foot Care', 'description' => 'Restorative hand creams and softening heel balms.'],
                    ['slug' => 'deodorants', 'name' => 'Natural Deodorants', 'description' => 'Aluminum-free botanical stick and cream deodorants.'],
                ],
            ],
            [
                'slug' => 'men',
                'name' => 'Men',
                'description' => 'Targeted grooming, beard care, shaving, and skincare for men.',
                'sort_order' => 6,
                'children' => [
                    ['slug' => 'shaving-beard', 'name' => 'Shaving & Beard Care', 'description' => 'Shave creams, aftershave balms, and softening beard oils.'],
                    ['slug' => 'mens-skincare', 'name' => "Men's Face Care", 'description' => 'Energizing cleansers, oil-control gels, and SPF moisturizers.'],
                    ['slug' => 'mens-hair', 'name' => "Men's Hair & Scalp", 'description' => 'Fortifying shampoos, anti-dandruff care, and matte pomades.'],
                    ['slug' => 'mens-body', 'name' => "Men's Shower & Body", 'description' => 'All-in-one body washes and long-lasting deodorants.'],
                ],
            ],
        ];

        DB::transaction(function () use ($departments, $seedImages) {
            foreach ($departments as $dept) {
                $parent = Category::updateOrCreate(
                    ['slug' => $dept['slug']],
                    [
                        'parent_id' => null,
                        'name' => $dept['name'],
                        'description' => $dept['description'],
                        'image' => $seedImages->productImage($dept['slug'], $dept['slug']),
                        'sort_order' => $dept['sort_order'],
                        'is_active' => true,
                    ]
                );

                $childSort = 0;
                foreach ($dept['children'] as $child) {
                    Category::updateOrCreate(
                        ['slug' => $child['slug']],
                        [
                            'parent_id' => $parent->id,
                            'name' => $child['name'],
                            'description' => $child['description'],
                            'image' => $seedImages->productImage($this->resolveChildDomain($child['slug'], $dept['slug']), $child['slug']),
                            'sort_order' => $childSort++,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });
    }

    /**
     * Pick the photo domain for a child category from its slug, defaulting to its department.
     */
    protected function resolveChildDomain(string $slug, string $departmentSlug): string
    {
        return match (true) {
            str_contains($slug, 'brush') || str_contains($slug, 'sponge') || str_contains($slug, 'tool') => 'accessories',
            str_contains($slug, 'perfume') || str_contains($slug, 'fragrance') || str_contains($slug, 'mist') => 'perfumes',
            str_contains($slug, 'shav') || str_contains($slug, 'beard') || str_contains($slug, 'mens') => 'men',
            str_contains($slug, 'shampoo') || str_contains($slug, 'conditioner') || str_contains($slug, 'hair') => 'hair',
            str_contains($slug, 'body') || str_contains($slug, 'bath') || str_contains($slug, 'scrub') => 'body-care',
            str_contains($slug, 'serum') || str_contains($slug, 'cream') || str_contains($slug, 'moistur')
                || str_contains($slug, 'cleans') || str_contains($slug, 'remover') || str_contains($slug, 'wipe') => 'skincare',
            str_contains($slug, 'lip') || str_contains($slug, 'eye') || str_contains($slug, 'lash') || str_contains($slug, 'brow')
                || str_contains($slug, 'nail') || str_contains($slug, 'palette') || str_contains($slug, 'mascara') => 'makeup',
            default => $departmentSlug,
        };
    }
}
