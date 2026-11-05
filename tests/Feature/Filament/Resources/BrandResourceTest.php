<?php

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Filament Brand Management', function () {
    beforeEach(function () {
        $this->admin = AdminUser::factory()->superAdmin()->create();
    });

    it('can render the brand list page and see brand records', function () {
        $brand1 = Brand::factory()->create([
            'name' => 'Maybelline New York',
            'country_of_origin' => 'US',
            'is_active' => true,
        ]);

        $brand2 = Brand::factory()->create([
            'name' => 'L\'Oréal Paris',
            'country_of_origin' => 'FR',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ListBrands::class)
            ->assertCanSeeTableRecords([$brand1, $brand2])
            ->searchTable('Maybelline')
            ->assertCanSeeTableRecords([$brand1])
            ->assertCanNotSeeTableRecords([$brand2]);
    });

    it('displays product count badge for brand', function () {
        $brand = Brand::factory()->create([
            'name' => 'CeraVe',
        ]);

        Product::factory()->count(3)->create([
            'brand_id' => $brand->id,
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ListBrands::class)
            ->assertCanSeeTableRecords([$brand])
            ->assertTableColumnStateSet('products_count', 3, $brand);
    });

    it('allows admin to create a new brand with valid attributes', function () {
        Livewire::actingAs($this->admin, 'admin')
            ->test(CreateBrand::class)
            ->fillForm([
                'name' => 'The Ordinary',
                'slug' => 'the-ordinary',
                'country_of_origin' => 'CA',
                'website_url' => 'https://theordinary.com',
                'description' => 'Clinical formulations with integrity.',
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $brand = Brand::where('slug', 'the-ordinary')->first();
        expect($brand)->not->toBeNull();
        expect($brand->name)->toBe('The Ordinary');
        expect($brand->country_of_origin)->toBe('CA');
        expect($brand->website_url)->toBe('https://theordinary.com');
    });

    it('allows admin to edit an existing brand', function () {
        $brand = Brand::factory()->create([
            'name' => 'La Roche',
            'slug' => 'la-roche',
            'country_of_origin' => 'FR',
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(EditBrand::class, ['record' => $brand->id])
            ->fillForm([
                'name' => 'La Roche-Posay',
                'slug' => 'la-roche-posay',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($brand->fresh()->name)->toBe('La Roche-Posay');
        expect($brand->fresh()->slug)->toBe('la-roche-posay');
    });

    it('allows admin to soft delete a brand', function () {
        $brand = Brand::factory()->create(['name' => 'Old Brand']);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ListBrands::class)
            ->callTableAction('delete', $brand);

        expect($brand->fresh()->trashed())->toBeTrue();
    });
});
