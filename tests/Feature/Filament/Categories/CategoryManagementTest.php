<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\RelationManagers\ChildrenRelationManager;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

describe('Filament Category Management', function () {
    beforeEach(function () {
        $this->admin = AdminUser::factory()->superAdmin()->create();
    });

    it('only displays root main categories and excludes subcategories from the list table', function () {
        $mainCategory = Category::factory()->create([
            'name' => 'Skincare',
            'slug' => 'skincare',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $subCategory = Category::factory()->create([
            'name' => 'Face Serums',
            'slug' => 'face-serums',
            'parent_id' => $mainCategory->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ListCategories::class)
            ->assertCanSeeTableRecords([$mainCategory])
            ->assertCanNotSeeTableRecords([$subCategory]);
    });

    it('displays subcategories and products count badges on main categories table', function () {
        $mainCategory = Category::factory()->create([
            'name' => 'Body Care',
            'slug' => 'body-care',
            'parent_id' => null,
        ]);

        Category::factory()->count(3)->create([
            'parent_id' => $mainCategory->id,
        ]);

        Product::factory()->count(2)->create([
            'category_id' => $mainCategory->id,
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ListCategories::class)
            ->assertCanSeeTableRecords([$mainCategory])
            ->assertTableColumnStateSet('children_count', 3, $mainCategory)
            ->assertTableColumnStateSet('products_count', 2, $mainCategory);
    });

    it('allows admin to create a root category without parent_id', function () {
        Livewire::actingAs($this->admin, 'admin')
            ->test(CreateCategory::class)
            ->fillForm([
                'name' => 'Fragrance & Mists',
                'description' => 'Pure essential oil fragrances.',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'fragrance-mists')->first();
        expect($category)->not->toBeNull();
        expect($category->parent_id)->toBeNull();
        expect($category->name)->toBe('Fragrance & Mists');
    });

    it('renders subcategories relation manager for a main category', function () {
        $mainCategory = Category::factory()->create(['name' => 'Aromatherapy']);
        $sub1 = Category::factory()->create(['name' => 'Diffuser Blends', 'parent_id' => $mainCategory->id]);
        $sub2 = Category::factory()->create(['name' => 'Roll-ons', 'parent_id' => $mainCategory->id]);

        $otherCategory = Category::factory()->create(['name' => 'Other Category']);
        $otherSub = Category::factory()->create(['name' => 'Unrelated Sub', 'parent_id' => $otherCategory->id]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ChildrenRelationManager::class, [
                'ownerRecord' => $mainCategory,
                'pageClass' => EditCategory::class,
            ])
            ->assertCanSeeTableRecords([$sub1, $sub2])
            ->assertCanNotSeeTableRecords([$otherSub]);
    });

    it('allows creating a nested subcategory from the relation manager modal', function () {
        $mainCategory = Category::factory()->create(['name' => 'Hair Rituals']);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ChildrenRelationManager::class, [
                'ownerRecord' => $mainCategory,
                'pageClass' => EditCategory::class,
            ])
            ->callTableAction('create', data: [
                'name' => 'Scalp Oils',
                'slug' => 'scalp-oils',
                'description' => 'Nourishing botanical scalp oils.',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $created = Category::where('slug', 'scalp-oils')->first();
        expect($created)->not->toBeNull();
        expect($created->parent_id)->toBe($mainCategory->id);
        expect($created->name)->toBe('Scalp Oils');
    });

    it('allows editing a subcategory within the relation manager', function () {
        $mainCategory = Category::factory()->create(['name' => 'Bath']);
        $sub = Category::factory()->create([
            'name' => 'Bath Salts',
            'slug' => 'bath-salts',
            'parent_id' => $mainCategory->id,
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ChildrenRelationManager::class, [
                'ownerRecord' => $mainCategory,
                'pageClass' => EditCategory::class,
            ])
            ->callTableAction('edit', $sub, data: [
                'name' => 'Epsom & Herbal Soaks',
            ])
            ->assertHasNoTableActionErrors();

        expect($sub->fresh()->name)->toBe('Epsom & Herbal Soaks');
    });

    it('allows soft deleting a subcategory from the relation manager', function () {
        $mainCategory = Category::factory()->create(['name' => 'Tools']);
        $sub = Category::factory()->create([
            'name' => 'Gua Sha Stones',
            'slug' => 'gua-sha-stones',
            'parent_id' => $mainCategory->id,
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ChildrenRelationManager::class, [
                'ownerRecord' => $mainCategory,
                'pageClass' => EditCategory::class,
            ])
            ->callTableAction('delete', $sub);

        expect($sub->fresh()->trashed())->toBeTrue();
    });
});
