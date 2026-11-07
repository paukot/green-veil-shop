<?php

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Models\AdminUser;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

describe('Filament Product Variants Relation Manager', function () {
    beforeEach(function () {
        $this->admin = AdminUser::factory()->superAdmin()->create();
    });

    it('renders the variants relation manager for a product', function () {
        $product = Product::factory()->create(['name' => 'Lash Sensational Sky High']);
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '01 Very Black',
            'variant_type' => 'color',
            'color_hex' => '#121212',
            'price' => 30.79,
        ]);
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '02 Cosmic Black',
            'variant_type' => 'color',
            'color_hex' => '#050505',
            'price' => 31.89,
        ]);

        $otherProduct = Product::factory()->create();
        $otherVariant = ProductVariant::factory()->create(['product_id' => $otherProduct->id, 'name' => 'Unrelated']);

        Livewire::actingAs($this->admin, 'admin')
            ->test(VariantsRelationManager::class, [
                'ownerRecord' => $product,
                'pageClass' => EditProduct::class,
            ])
            ->assertCanSeeTableRecords([$variant1, $variant2])
            ->assertCanNotSeeTableRecords([$otherVariant]);
    });

    it('allows creating a new shade variant with color hex swatch from the modal', function () {
        $product = Product::factory()->create(['name' => 'Matte Ink Liquid Lipstick']);

        Livewire::actingAs($this->admin, 'admin')
            ->test(VariantsRelationManager::class, [
                'ownerRecord' => $product,
                'pageClass' => EditProduct::class,
            ])
            ->callTableAction('create', data: [
                'variant_type' => 'color',
                'name' => '15 Lover',
                'color_hex' => '#c25e68',
                'sku' => 'MAY-MAT-15',
                'price' => 28.50,
                'stock_quantity' => 20,
                'is_default' => true,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $variant = ProductVariant::where('sku', 'MAY-MAT-15')->first();
        expect($variant)->not->toBeNull();
        expect($variant->product_id)->toBe($product->id);
        expect($variant->name)->toBe('15 Lover');
        expect($variant->color_hex)->toBe('#c25e68');
        expect((float) $variant->price)->toBe(28.50);
        expect($variant->stock_quantity)->toBe(20);
        expect($variant->is_default)->toBeTrue();
    });

    it('allows editing an existing product variant', function () {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '50 ml',
            'variant_type' => 'size',
            'price' => 45.00,
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(VariantsRelationManager::class, [
                'ownerRecord' => $product,
                'pageClass' => EditProduct::class,
            ])
            ->callTableAction('edit', $variant, data: [
                'price' => 49.90,
                'stock_quantity' => 100,
            ])
            ->assertHasNoTableActionErrors();

        expect((float) $variant->fresh()->price)->toBe(49.90);
        expect($variant->fresh()->stock_quantity)->toBe(100);
    });

    it('allows soft deleting a product variant', function () {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(VariantsRelationManager::class, [
                'ownerRecord' => $product,
                'pageClass' => EditProduct::class,
            ])
            ->callTableAction('delete', $variant);

        expect($variant->fresh()->trashed())->toBeTrue();
    });
});
