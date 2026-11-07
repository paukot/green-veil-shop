<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Effect;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\SkinType;
use Livewire\Livewire;

describe('Filament Product Cosmetic Management', function () {
    beforeEach(function () {
        $this->admin = AdminUser::factory()->superAdmin()->create();
    });

    it('renders the product list table with brand badge, category name and variants count', function () {
        $brand = Brand::factory()->create(['name' => 'Maybelline New York']);
        $category = Category::factory()->create(['name' => 'Mascaras', 'slug' => 'mascaras']);
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Lash Sensational Sky High',
            'price' => 30.79,
        ]);

        $product->variants()->create([
            'name' => '01 Very Black',
            'price' => 30.79,
            'stock_quantity' => 10,
            'color_hex' => '#121212',
            'variant_type' => 'color',
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ListProducts::class)
            ->assertCanSeeTableRecords([$product])
            ->assertTableColumnStateSet('brand.name', 'Maybelline New York', $product)
            ->assertTableColumnStateSet('category.name', 'Mascaras', $product)
            ->assertTableColumnStateSet('variants_count', 1, $product);
    });

    it('allows admin to create a product with rich cosmetic details across tabs', function () {
        $brand = Brand::factory()->create(['name' => 'L\'Oréal Paris']);
        $category = Category::factory()->create(['name' => 'Face Creams']);
        $skinType = SkinType::create(['name' => 'Mature', 'slug' => 'mature']);
        $effect = Effect::create(['name' => 'Firming', 'slug' => 'firming']);
        $ingredient = Ingredient::create(['name' => 'Hyaluronic Acid', 'slug' => 'hyaluronic-acid']);
        $claim = Claim::create(['name' => 'Dermatologist Tested', 'slug' => 'dermatologist-tested']);

        Livewire::actingAs($this->admin, 'admin')
            ->test(CreateProduct::class)
            ->fillForm([
                'name' => 'Revitalift Laser Pure Retinol Deep Night Serum',
                'slug' => 'revitalift-laser-pure-retinol-deep-night-serum',
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'series' => 'Revitalift',
                'price' => 24.99,
                'compare_at_price' => 29.99,
                'volume' => 30.00,
                'unit' => 'ml',
                'unit_price_base_amount' => 100,
                'classification' => 'drugstore',
                'gender' => 'unisex',
                'age_range' => '30+',
                'formula_type' => 'serum',
                'spf' => 'none',
                'skinTypes' => [$skinType->id],
                'effects' => [$effect->id],
                'ingredients' => [$ingredient->id],
                'claims' => [$claim->id],
                'custom_characteristics' => [
                    'Aroma' => 'Fragrance-free',
                    'Texture' => 'Lightweight fluid',
                ],
                'inci_list' => 'Aqua/Water, Glycerin, Alcohol Denat., Glycine Soja Oil, Isononyl Isononanoate, Retinol, Sodium Hyaluronate...',
                'how_to_use' => 'Apply 3-4 drops to clean, dry skin in the evening before moisturizer. Always use SPF 20+ the next morning.',
                'warnings' => 'Initial redness or dryness may occur. If irritation persists, reduce frequency of use.',
                'manufactured_in' => 'FR',
                'pao_months' => 6,
                'responsible_person' => 'L\'Oréal, 14 rue Royale 75008 Paris',
                'sku' => 'LOR-REV-01',
                'stock_quantity' => 50,
                'track_inventory' => true,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'revitalift-laser-pure-retinol-deep-night-serum')->first();
        expect($product)->not->toBeNull();
        expect($product->brand_id)->toBe($brand->id);
        expect($product->series)->toBe('Revitalift');
        expect((float) $product->volume)->toBe(30.0);
        expect($product->unit)->toBe('ml');
        expect($product->unit_price_base_amount)->toBe(100);
        expect($product->skinTypes->pluck('slug')->all())->toContain('mature');
        expect($product->effects->pluck('slug')->all())->toContain('firming');
        expect($product->ingredients->pluck('slug')->all())->toContain('hyaluronic-acid');
        expect($product->claims->pluck('slug')->all())->toContain('dermatologist-tested');
        expect($product->custom_characteristics)->toHaveKey('Texture', 'Lightweight fluid');
        expect($product->calculateUnitPrice())->toBe(83.30);
        expect($product->formattedUnitPrice())->toBe('83.30 € / 100 ml');
    });

    it('allows admin to edit cosmetic specifications on an existing product', function () {
        $product = Product::factory()->create([
            'name' => 'Original Botanical Tonic',
            'classification' => 'natural_organic',
            'pao_months' => 12,
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(EditProduct::class, ['record' => $product->id])
            ->fillForm([
                'classification' => 'dermocosmetic',
                'pao_months' => 6,
                'spf' => 'SPF 30',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($product->fresh()->classification)->toBe('dermocosmetic');
        expect($product->fresh()->pao_months)->toBe(6);
        expect($product->fresh()->spf)->toBe('SPF 30');
    });
});
