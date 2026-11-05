<?php

namespace Database\Seeders;

use App\Models\Claim;
use App\Models\Effect;
use App\Models\HairType;
use App\Models\Ingredient;
use App\Models\SkinType;
use Illuminate\Database\Seeder;

class CosmeticLookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skinTypes = [
            ['name' => 'All Skin Types', 'slug' => 'all-skin-types', 'sort_order' => 1],
            ['name' => 'Dry', 'slug' => 'dry', 'sort_order' => 2],
            ['name' => 'Very Dry', 'slug' => 'very-dry', 'sort_order' => 3],
            ['name' => 'Combination', 'slug' => 'combination', 'sort_order' => 4],
            ['name' => 'Oily', 'slug' => 'oily', 'sort_order' => 5],
            ['name' => 'Sensitive', 'slug' => 'sensitive', 'sort_order' => 6],
            ['name' => 'Mature', 'slug' => 'mature', 'sort_order' => 7],
            ['name' => 'Acne-Prone', 'slug' => 'acne-prone', 'sort_order' => 8],
            ['name' => 'Dehydrated', 'slug' => 'dehydrated', 'sort_order' => 9],
            ['name' => 'Couperose', 'slug' => 'couperose', 'sort_order' => 10],
            ['name' => 'Normal', 'slug' => 'normal', 'sort_order' => 11],
            ['name' => 'Sensitive Eyes', 'slug' => 'sensitive-eyes', 'sort_order' => 12],
        ];
        foreach ($skinTypes as $item) {
            SkinType::firstOrCreate(['slug' => $item['slug']], $item);
        }

        $hairTypes = [
            ['name' => 'All Hair Types', 'slug' => 'all-hair-types', 'sort_order' => 1],
            ['name' => 'Dry & Dehydrated', 'slug' => 'dry-dehydrated', 'sort_order' => 2],
            ['name' => 'Damaged & Brittle', 'slug' => 'damaged-brittle', 'sort_order' => 3],
            ['name' => 'Fine & Thinning', 'slug' => 'fine-thinning', 'sort_order' => 4],
            ['name' => 'Curly & Wavy', 'slug' => 'curly-wavy', 'sort_order' => 5],
            ['name' => 'Color-Treated', 'slug' => 'color-treated', 'sort_order' => 6],
            ['name' => 'Frizzy', 'slug' => 'frizzy', 'sort_order' => 7],
            ['name' => 'Oily Scalp', 'slug' => 'oily-scalp', 'sort_order' => 8],
        ];
        foreach ($hairTypes as $item) {
            HairType::firstOrCreate(['slug' => $item['slug']], $item);
        }

        $effects = [
            ['name' => 'Moisturizing', 'slug' => 'moisturizing', 'sort_order' => 1],
            ['name' => 'Volumizing', 'slug' => 'volumizing', 'sort_order' => 2],
            ['name' => 'Lengthening', 'slug' => 'lengthening', 'sort_order' => 3],
            ['name' => 'Anti-Aging', 'slug' => 'anti-aging', 'sort_order' => 4],
            ['name' => 'Nourishing', 'slug' => 'nourishing', 'sort_order' => 5],
            ['name' => 'Soothing', 'slug' => 'soothing', 'sort_order' => 6],
            ['name' => 'Firming', 'slug' => 'firming', 'sort_order' => 7],
            ['name' => 'Mattifying', 'slug' => 'mattifying', 'sort_order' => 8],
            ['name' => 'Cleansing', 'slug' => 'cleansing', 'sort_order' => 9],
            ['name' => 'Waterproof', 'slug' => 'waterproof', 'sort_order' => 10],
            ['name' => 'Long-Lasting (24h)', 'slug' => 'long-lasting', 'sort_order' => 11],
            ['name' => 'Radiance & Glow', 'slug' => 'radiance-glow', 'sort_order' => 12],
            ['name' => 'Strengthening', 'slug' => 'strengthening', 'sort_order' => 13],
            ['name' => 'Lash Separation', 'slug' => 'lash-separation', 'sort_order' => 14],
        ];
        foreach ($effects as $item) {
            Effect::firstOrCreate(['slug' => $item['slug']], $item);
        }

        $ingredients = [
            ['name' => 'Hyaluronic Acid', 'slug' => 'hyaluronic-acid', 'inci_name' => 'Sodium Hyaluronate', 'sort_order' => 1],
            ['name' => 'Niacinamide', 'slug' => 'niacinamide', 'inci_name' => 'Niacinamide', 'sort_order' => 2],
            ['name' => 'Vitamin C', 'slug' => 'vitamin-c', 'inci_name' => 'Ascorbic Acid', 'sort_order' => 3],
            ['name' => 'Retinol', 'slug' => 'retinol', 'inci_name' => 'Retinol', 'sort_order' => 4],
            ['name' => 'Salicylic Acid', 'slug' => 'salicylic-acid', 'inci_name' => 'Salicylic Acid', 'sort_order' => 5],
            ['name' => 'Glycolic Acid', 'slug' => 'glycolic-acid', 'inci_name' => 'Glycolic Acid', 'sort_order' => 6],
            ['name' => 'Ceramides', 'slug' => 'ceramides', 'inci_name' => 'Ceramide NP', 'sort_order' => 7],
            ['name' => 'Peptides', 'slug' => 'peptides', 'inci_name' => 'Palmitoyl Tripeptide-1', 'sort_order' => 8],
            ['name' => 'Squalane', 'slug' => 'squalane', 'inci_name' => 'Squalane', 'sort_order' => 9],
            ['name' => 'Centella Asiatica', 'slug' => 'centella-asiatica', 'inci_name' => 'Centella Asiatica Extract', 'sort_order' => 10],
            ['name' => 'Bamboo Extract', 'slug' => 'bamboo-extract', 'inci_name' => 'Bambusa Vulgaris Extract', 'sort_order' => 11],
            ['name' => 'Argan Oil', 'slug' => 'argan-oil', 'inci_name' => 'Argania Spinosa Kernel Oil', 'sort_order' => 12],
            ['name' => 'Jojoba Oil', 'slug' => 'jojoba-oil', 'inci_name' => 'Simmondsia Chinensis Seed Oil', 'sort_order' => 13],
            ['name' => 'Shea Butter', 'slug' => 'shea-butter', 'inci_name' => 'Butyrospermum Parkii Butter', 'sort_order' => 14],
            ['name' => 'Panthenol', 'slug' => 'panthenol', 'inci_name' => 'Panthenol', 'sort_order' => 15],
            ['name' => 'Vitamin E', 'slug' => 'vitamin-e', 'inci_name' => 'Tocopherol', 'sort_order' => 16],
            ['name' => 'Tea Tree Oil', 'slug' => 'tea-tree-oil', 'inci_name' => 'Melaleuca Alternifolia Leaf Oil', 'sort_order' => 17],
            ['name' => 'Collagen', 'slug' => 'collagen', 'inci_name' => 'Hydrolyzed Collagen', 'sort_order' => 18],
            ['name' => 'Caffeine', 'slug' => 'caffeine', 'inci_name' => 'Caffeine', 'sort_order' => 19],
            ['name' => 'Aloe Vera', 'slug' => 'aloe-vera', 'inci_name' => 'Aloe Barbadensis Leaf Juice', 'sort_order' => 20],
        ];
        foreach ($ingredients as $item) {
            Ingredient::firstOrCreate(['slug' => $item['slug']], $item);
        }

        $claims = [
            ['name' => 'Vegan Formula', 'slug' => 'vegan', 'sort_order' => 1],
            ['name' => 'Cruelty-Free', 'slug' => 'cruelty-free', 'sort_order' => 2],
            ['name' => 'Hypoallergenic', 'slug' => 'hypoallergenic', 'sort_order' => 3],
            ['name' => 'Dermatologist Tested', 'slug' => 'dermatologist-tested', 'sort_order' => 4],
            ['name' => 'Ophthalmologist Tested', 'slug' => 'ophthalmologist-tested', 'sort_order' => 5],
            ['name' => 'Paraben-Free', 'slug' => 'paraben-free', 'sort_order' => 6],
            ['name' => 'Silicone-Free', 'slug' => 'silicone-free', 'sort_order' => 7],
            ['name' => 'Microplastic-Free', 'slug' => 'microplastic-free', 'sort_order' => 8],
            ['name' => 'Certified Organic', 'slug' => 'organic-certified', 'sort_order' => 9],
            ['name' => 'Non-Comedogenic', 'slug' => 'non-comedogenic', 'sort_order' => 10],
            ['name' => 'Fragrance-Free', 'slug' => 'fragrance-free', 'sort_order' => 11],
            ['name' => 'Alcohol-Free', 'slug' => 'alcohol-free', 'sort_order' => 12],
        ];
        foreach ($claims as $item) {
            Claim::firstOrCreate(['slug' => $item['slug']], $item);
        }
    }
}
